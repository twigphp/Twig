<?php

namespace Twig\Tests;

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Error\SyntaxError;
use Twig\Loader\LoaderInterface;
use Twig\Node\Node;
use Twig\Node\SetNode;
use Twig\Node\TextNode;
use Twig\Parser;
use Twig\Source;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;
use Twig\TokenStream;

class ParserTest extends TestCase
{
    public function testUnknownTag()
    {
        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage('Unknown "foo" tag. Did you mean "for" at line 1?');

        $stream = new TokenStream([
            new Token(Token::BLOCK_START_TYPE, '', 1),
            new Token(Token::NAME_TYPE, 'foo', 1),
            new Token(Token::BLOCK_END_TYPE, '', 1),
            new Token(Token::EOF_TYPE, '', 1),
        ]);
        $parser = new Parser(new Environment($this->createMock(LoaderInterface::class)));
        $parser->parse($stream);
    }

    public function testUnknownTagWithoutSuggestions()
    {
        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage('Unknown "foobar" tag at line 1.');

        $stream = new TokenStream([
            new Token(Token::BLOCK_START_TYPE, '', 1),
            new Token(Token::NAME_TYPE, 'foobar', 1),
            new Token(Token::BLOCK_END_TYPE, '', 1),
            new Token(Token::EOF_TYPE, '', 1),
        ]);
        $parser = new Parser(new Environment($this->createMock(LoaderInterface::class)));
        $parser->parse($stream);
    }

    /**
     * @dataProvider getFilterBodyNodesData
     */
    public function testFilterBodyNodes($input, $expected)
    {
        $parser = $this->getParser();
        $m = new \ReflectionMethod($parser, 'filterBodyNodes');
        $m->setAccessible(true);

        $this->assertEquals($expected, $m->invoke($parser, $input));
    }

    public function getFilterBodyNodesData()
    {
        return [
            [
                new Node([new TextNode('   ', 1)]),
                new Node([]),
            ],
            [
                $input = new Node([new SetNode(false, new Node(), new Node(), 1)]),
                $input,
            ],
            [
                $input = new Node([new SetNode(true, new Node(), new Node([new Node([new TextNode('foo', 1)])]), 1)]),
                $input,
            ],
        ];
    }

    /**
     * @dataProvider getFilterBodyNodesDataThrowsException
     */
    public function testFilterBodyNodesThrowsException($input)
    {
        $this->expectException(SyntaxError::class);

        $parser = $this->getParser();

        $m = new \ReflectionMethod($parser, 'filterBodyNodes');
        $m->setAccessible(true);

        $m->invoke($parser, $input);
    }

    public function getFilterBodyNodesDataThrowsException()
    {
        return [
            [new TextNode('foo', 1)],
            [new Node([new Node([new TextNode('foo', 1)])])],
        ];
    }

    /**
     * @dataProvider getFilterBodyNodesWithBOMData
     */
    public function testFilterBodyNodesWithBOM($emptyNode)
    {
        $parser = $this->getParser();

        $m = new \ReflectionMethod($parser, 'filterBodyNodes');
        $m->setAccessible(true);
        $this->assertNull($m->invoke($parser, new TextNode(\chr(0xEF).\chr(0xBB).\chr(0xBF).$emptyNode, 1)));
    }

    public function getFilterBodyNodesWithBOMData()
    {
        return [
            [' '],
            ["\t"],
            ["\n"],
            ["\n\t\n   "],
        ];
    }

    public function testParseIsReentrant()
    {
        $twig = new Environment($this->createMock(LoaderInterface::class), [
            'autoescape' => false,
            'optimizations' => 0,
        ]);
        $twig->addTokenParser(new TestTokenParser());

        $parser = new Parser($twig);

        $parser->parse(new TokenStream([
            new Token(Token::BLOCK_START_TYPE, '', 1),
            new Token(Token::NAME_TYPE, 'test', 1),
            new Token(Token::BLOCK_END_TYPE, '', 1),
            new Token(Token::VAR_START_TYPE, '', 1),
            new Token(Token::NAME_TYPE, 'foo', 1),
            new Token(Token::VAR_END_TYPE, '', 1),
            new Token(Token::EOF_TYPE, '', 1),
        ]));

        $this->assertNull($parser->getParent());
    }

    public function testGetVarName()
    {
        $twig = new Environment($this->createMock(LoaderInterface::class), [
            'autoescape' => false,
            'optimizations' => 0,
        ]);

        $twig->parse($twig->tokenize(new Source(<<<EOF
{% from _self import foo %}

{% macro foo() %}
    {{ foo }}
{% endmacro %}
EOF
        , 'index')));

        // The getVarName() must not depend on the template loaders,
        // If this test does not throw any exception, that's good.
        $this->addToAssertionCount(1);
    }

    protected function getParser()
    {
        $parser = new Parser(new Environment($this->createMock(LoaderInterface::class)));
        $parser->setParent(new Node());

        $p = new \ReflectionProperty($parser, 'stream');
        $p->setAccessible(true);
        $p->setValue($parser, new TokenStream([]));

        return $parser;
    }
}

class TestTokenParser extends AbstractTokenParser
{
    public function parse(Token $token): Node
    {
        // simulate the parsing of another template right in the middle of the parsing of the current template
        $this->parser->parse(new TokenStream([
            new Token(Token::BLOCK_START_TYPE, '', 1),
            new Token(Token::NAME_TYPE, 'extends', 1),
            new Token(Token::STRING_TYPE, 'base', 1),
            new Token(Token::BLOCK_END_TYPE, '', 1),
            new Token(Token::EOF_TYPE, '', 1),
        ]));

        $this->parser->getStream()->expect(Token::BLOCK_END_TYPE);

        return new Node([]);
    }

    public function getTag(): string
    {
        return 'test';
    }
}
