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
use Twig\Lexer;
use Twig\Loader\ArrayLoader;
use Twig\Node\Node;
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
        ]);
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
        ]);
        $parser = new Parser(new Environment(new ArrayLoader()));

        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage('Unknown "foobar" tag at line 1.');

        $parser->parse($stream);
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
        ]));

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

        $this->assertTrue($argumentNodes->getNode('po')->hasAttribute('is_implicit'));
        $this->assertTrue($argumentNodes->getNode('po')->getAttribute('is_implicit'));
        $this->assertNull($argumentNodes->getNode('po')->getAttribute('value'));

        $this->assertFalse($argumentNodes->getNode('lo')->hasAttribute('is_implicit'));
        $this->assertTrue($argumentNodes->getNode('lo')->getAttribute('value'));
    }

    public function testBodyForChildTemplates()
    {
        $twig = new Environment(new ArrayLoader());
        $node = $twig->parse($twig->tokenize(new Source(<<<EOF
{% extends "base" %}

{% block header %}
    header
{% endblock %}

{% set foo = 'bar' %}

{% block footer %}
    footer
{% endblock %}

EOF
            , 'index')));

        $body = $node->getNode('body')->getNode('0');
        $this->assertCount(2, $body);
        $this->assertSame('extends', $body->getNode('0')->getNodeTag());
        $this->assertSame('set', $body->getNode('4')->getNodeTag());
    }

    public function testBodyForParentTemplates()
    {
        $twig = new Environment(new ArrayLoader());
        $node = $twig->parse($twig->tokenize(new Source(<<<EOF
{% block header %}
    header
{% endblock %}

{% set foo = 'bar' %}

{% block footer %}
    footer
{% endblock %}

EOF
            , 'index')));

        $body = $node->getNode('body')->getNode('0');
        $this->assertCount(5, $body);
        $this->assertSame('block', $body->getNode('0')->getNodeTag());
        $this->assertInstanceOf(TextNode::class, $body->getNode('1'));
        $this->assertSame('set', $body->getNode('2')->getNodeTag());
        $this->assertInstanceOf(TextNode::class, $body->getNode('3'));
        $this->assertSame('block', $body->getNode('4')->getNodeTag());
    }

    protected function getParser()
    {
        $parser = new Parser(new Environment(new ArrayLoader()));
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

        return new Node([], [], 1);
    }

    public function getTag(): string
    {
        return 'test';
    }
}
