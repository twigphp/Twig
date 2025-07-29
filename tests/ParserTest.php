<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
use Twig\Lexer;
use Twig\Loader\ArrayLoader;
use Twig\Node\EmptyNode;
use Twig\Node\Node;
use Twig\Node\Nodes;
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
        $stream = new TokenStream([
            new Token(Token::BLOCK_START_TYPE, '', 1),
            new Token(Token::NAME_TYPE, 'foo', 1),
            new Token(Token::BLOCK_END_TYPE, '', 1),
            new Token(Token::EOF_TYPE, '', 1),
        ], new Source('', ''));
        $parser = new Parser(new Environment(new ArrayLoader()));

        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage('Unknown "foo" tag. Did you mean "for" at line 1?');

        $parser->parse($stream);
    }

    public function testUnknownTagWithoutSuggestions()
    {
        $stream = new TokenStream([
            new Token(Token::BLOCK_START_TYPE, '', 1),
            new Token(Token::NAME_TYPE, 'foobar', 1),
            new Token(Token::BLOCK_END_TYPE, '', 1),
            new Token(Token::EOF_TYPE, '', 1),
        ], new Source('', ''));
        $parser = new Parser(new Environment(new ArrayLoader()));

        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage('Unknown "foobar" tag at line 1.');

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

    public static function getFilterBodyNodesData()
    {
        return [
            [
                new Nodes([new TextNode('   ', 1)]),
                new Nodes([]),
            ],
            [
                $input = new Nodes([new SetNode(false, new EmptyNode(), new EmptyNode(), 1)]),
                $input,
            ],
            [
                $input = new Nodes([new SetNode(true, new EmptyNode(), new Nodes([new Nodes([new TextNode('foo', 1)])]), 1)]),
                $input,
            ],
        ];
    }

    /**
     * @dataProvider getFilterBodyNodesDataThrowsException
     */
    public function testFilterBodyNodesThrowsException($input)
    {
        $parser = $this->getParser();

        $m = new \ReflectionMethod($parser, 'filterBodyNodes');
        $m->setAccessible(true);

        $this->expectException(SyntaxError::class);
        $m->invoke($parser, $input);
    }

    public static function getFilterBodyNodesDataThrowsException()
    {
        return [
            [new TextNode('foo', 1)],
            [new Nodes([new Nodes([new TextNode('foo', 1)])])],
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

    public static function getFilterBodyNodesWithBOMData()
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
        $twig = new Environment(new ArrayLoader(), [
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
        ], new Source('', '')));

        $p = new \ReflectionProperty($parser, 'parent');
        $p->setAccessible(true);
        $this->assertNull($p->getValue($parser));
    }

    public function testGetVarName()
    {
        $twig = new Environment(new ArrayLoader(), [
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

    public function testImplicitMacroArgumentDefaultValues()
    {
        $template = '{% macro marco (po, lo = true) %}{% endmacro %}';
        $lexer = new Lexer(new Environment(new ArrayLoader()));
        $stream = $lexer->tokenize(new Source($template, 'index'));

        $argumentNodes = $this->getParser()
            ->parse($stream)
            ->getNode('macros')
            ->getNode('marco')
            ->getNode('arguments')
        ;

        $this->assertTrue($argumentNodes->getNode(1)->hasAttribute('is_implicit'));
        $this->assertNull($argumentNodes->getNode(1)->getAttribute('value'));

        $this->assertFalse($argumentNodes->getNode(3)->hasAttribute('is_implicit'));
        $this->assertTrue($argumentNodes->getNode(3)->getAttribute('value'));
    }

    protected function getParser()
    {
        $parser = new Parser(new Environment(new ArrayLoader()));
        $parser->setParent(new EmptyNode());

        $p = new \ReflectionProperty($parser, 'stream');
        $p->setAccessible(true);
        $p->setValue($parser, new TokenStream([], new Source('', '')));

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
        ], new Source('', '')));

        $this->parser->getStream()->expect(Token::BLOCK_END_TYPE);

        return new EmptyNode(1);
    }

    public function getTag(): string
    {
        return 'test';
    }
}
