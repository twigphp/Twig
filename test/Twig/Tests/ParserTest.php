<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Twig\Environment;
use Twig\Node\Node;
use Twig\Node\SetNode;
use Twig\Node\TextNode;
use Twig\Parser;
use Twig\Source;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;
use Twig\TokenStream;

class Twig_Tests_ParserTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @expectedException \Twig\Error\SyntaxError
     */
    public function testSetMacroThrowsExceptionOnReservedMethods()
    {
        $parser = $this->getParser();
        $parser->setMacro('parent', $this->getMockBuilder('\Twig\Node\MacroNode')->disableOriginalConstructor()->getMock());
    }

    /**
     * @expectedException        \Twig\Error\SyntaxError
     * @expectedExceptionMessage Unknown "foo" tag. Did you mean "for" at line 1?
     */
    public function testUnknownTag()
    {
        $stream = new TokenStream([
            new Token(Token::BLOCK_START_TYPE, '', 1),
            new Token(Token::NAME_TYPE, 'foo', 1),
            new Token(Token::BLOCK_END_TYPE, '', 1),
            new Token(Token::EOF_TYPE, '', 1),
        ]);
        $parser = new Parser(new Environment($this->getMockBuilder('\Twig\Loader\LoaderInterface')->getMock()));
        $parser->parse($stream);
    }

    /**
     * @expectedException        \Twig\Error\SyntaxError
     * @expectedExceptionMessage Unknown "foobar" tag at line 1.
     */
    public function testUnknownTagWithoutSuggestions()
    {
        $stream = new TokenStream([
            new Token(Token::BLOCK_START_TYPE, '', 1),
            new Token(Token::NAME_TYPE, 'foobar', 1),
            new Token(Token::BLOCK_END_TYPE, '', 1),
            new Token(Token::EOF_TYPE, '', 1),
        ]);
        $parser = new Parser(new Environment($this->getMockBuilder('\Twig\Loader\LoaderInterface')->getMock()));
        $parser->parse($stream);
    }

    /**
     * @dataProvider getFilterBodyNodesData
     */
    public function testFilterBodyNodes($input, $expected)
    {
        $parser = $this->getParser();

        $this->assertEquals($expected, $parser->filterBodyNodes($input));
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
     * @expectedException \Twig\Error\SyntaxError
     */
    public function testFilterBodyNodesThrowsException($input)
    {
        $parser = $this->getParser();

        $parser->filterBodyNodes($input);
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
        $this->assertNull($this->getParser()->filterBodyNodes(new TextNode(\chr(0xEF).\chr(0xBB).\chr(0xBF).$emptyNode, 1)));
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
        $twig = new Environment($this->getMockBuilder('\Twig\Loader\LoaderInterface')->getMock(), [
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
        $twig = new Environment($this->getMockBuilder('\Twig\Loader\LoaderInterface')->getMock(), [
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
        // see https://github.com/symfony/symfony/issues/4218
        $this->addToAssertionCount(1);
    }

    protected function getParser()
    {
        $parser = new TestParser(new Environment($this->getMockBuilder('\Twig\Loader\LoaderInterface')->getMock()));
        $parser->setParent(new Node());
        $parser->stream = new TokenStream([]);

        return $parser;
    }
}

class TestParser extends Parser
{
    public $stream;

    public function filterBodyNodes(Twig_NodeInterface $node)
    {
        return parent::filterBodyNodes($node);
    }
}

class TestTokenParser extends AbstractTokenParser
{
    public function parse(Token $token)
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

    public function getTag()
    {
        return 'test';
    }
}
