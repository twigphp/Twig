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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Error\SyntaxError;
use Twig\Lexer;
use Twig\Loader\ArrayLoader;
use Twig\Node\EmptyNode;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\GetAttrExpression;
use Twig\Node\Expression\MacroReferenceExpression;
use Twig\Node\MacroDeclarationNode;
use Twig\Node\Node;
use Twig\Node\TextNode;
use Twig\Parser;
use Twig\Source;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;
use Twig\TokenStream;

class ParserTest extends TestCase
{
    public function testUnknownTag(): void
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

    public function testUnknownTagWithoutSuggestions(): void
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

    public function testParseIsReentrant(): void
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
        $this->assertNull($p->getValue($parser));
    }

    public function testGetVarName(): void
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
EOF, 'index')));

        // The getVarName() must not depend on the template loaders,
        // If this test does not throw any exception, that's good.
        $this->addToAssertionCount(1);
    }

    /**
     * @dataProvider provideMacroTargetExpressions
     */
    #[DataProvider('provideMacroTargetExpressions')]
    public function testMacroTargetsOnlyCompileAsMacroReferences(string $expression): void
    {
        $twig = new Environment(new ArrayLoader());
        $module = $twig->parse($twig->tokenize(new Source("{% import _self as macros %}{{ $expression }}", 'index')));
        $macroReferences = [];
        $attributeExpressions = [];

        $this->collectExpressions($module, $macroReferences, $attributeExpressions);

        $this->assertCount(1, $macroReferences);
        $this->assertSame([], $attributeExpressions);
    }

    public static function provideMacroTargetExpressions(): iterable
    {
        foreach (['_self', 'macros'] as $target) {
            yield $target.' static with parentheses' => [$target.'.foo()'];
            yield $target.' dynamic with parentheses' => [$target.'.(name)()'];
        }
    }

    #[DataProvider('provideMacroTargetExpressionsWithoutParentheses')]
    public function testMacroTargetsWithoutParenthesesAreRejectedAtParseTime(string $expression): void
    {
        $twig = new Environment(new ArrayLoader());

        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage('Omitting parentheses when calling a macro is not allowed; add parentheses after the macro name in "index" at line 1.');

        $twig->parse($twig->tokenize(new Source("{% import _self as macros %}{{ $expression }}", 'index')));
    }

    public static function provideMacroTargetExpressionsWithoutParentheses(): iterable
    {
        foreach (['_self', 'macros'] as $target) {
            yield $target.' static without parentheses' => [$target.'.foo'];
            yield $target.' dynamic without parentheses' => [$target.'.(name)'];
            yield $target.' static defined test without parentheses' => [$target.'.foo is defined'];
            yield $target.' dynamic defined test without parentheses' => [$target.'.(name) is defined'];
        }
    }

    public function testImplicitMacroArgumentDefaultValues(): void
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

    public function testMacroDeclarationIsRepresentedInTheTemplateBody(): void
    {
        $twig = new Environment(new ArrayLoader());
        $module = $twig->parse($twig->tokenize(new Source('{% macro input() %}{% endmacro %}', 'index')));
        $declaration = $module->getNode('body')->getNode('0');

        $this->assertInstanceOf(MacroDeclarationNode::class, $declaration);
        $this->assertSame('input', $declaration->getAttribute('name'));
        $this->assertSame('macro', $declaration->getNodeTag());
        $this->assertCount(1, $module->getNode('macros'));
        $this->assertNotSame($declaration, $module->getNode('macros')->getNode('input'));
    }

    public function testEmbeddedTemplatesHaveSequentialIndices(): void
    {
        $template = new Source('{% embed "first" %}{% endembed %}{% embed "second" %}{% endembed %}', 'index');
        $lexer = new Lexer(new Environment(new ArrayLoader()));
        $stream = $lexer->tokenize($template);

        $embeds = $this->getParser()
            ->parse($stream)
            ->getAttribute('embedded_templates');

        $this->assertSame(1, $embeds->getNode(0)->getAttribute('index'));
        $this->assertSame(2, $embeds->getNode(1)->getAttribute('index'));
    }

    public function testBodyForChildTemplates(): void
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

EOF, 'index')));

        $body = $node->getNode('body')->getNode('0');
        $this->assertCount(2, $body);
        $this->assertSame('extends', $body->getNode('0')->getNodeTag());
        $this->assertSame('set', $body->getNode('4')->getNodeTag());
    }

    public function testCleanupBodyForChildTemplatesWithASingleNodeBody(): void
    {
        $twig = new Environment(new ArrayLoader());
        $twig->addTokenParser(new ParentSettingTokenParser());

        $node = $twig->parse($twig->tokenize(new Source('{% set_parent %}', 'index')));

        $body = $node->getNode('body')->getNode('0');
        $this->assertInstanceOf(EmptyNode::class, $body);
    }

    public function testBodyForParentTemplates(): void
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

EOF, 'index')));

        $body = $node->getNode('body')->getNode('0');
        $this->assertCount(5, $body);
        $this->assertSame('block', $body->getNode('0')->getNodeTag());
        $this->assertInstanceOf(TextNode::class, $body->getNode('1'));
        $this->assertSame('set', $body->getNode('2')->getNodeTag());
        $this->assertInstanceOf(TextNode::class, $body->getNode('3'));
        $this->assertSame('block', $body->getNode('4')->getNodeTag());
    }

    /**
     * @param list<MacroReferenceExpression> $macroReferences
     * @param list<GetAttrExpression>        $attributeExpressions
     */
    private function collectExpressions(Node $node, array &$macroReferences, array &$attributeExpressions): void
    {
        if ($node instanceof MacroReferenceExpression) {
            $macroReferences[] = $node;
        }
        if ($node instanceof GetAttrExpression) {
            $attributeExpressions[] = $node;
        }

        foreach ($node as $child) {
            $this->collectExpressions($child, $macroReferences, $attributeExpressions);
        }
    }

    protected function getParser()
    {
        $parser = new Parser(new Environment(new ArrayLoader()));
        $parser->setParent(new ConstantExpression('base.html', 1));

        $p = new \ReflectionProperty($parser, 'stream');
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

class ParentSettingTokenParser extends AbstractTokenParser
{
    public function parse(Token $token): Node
    {
        $this->parser->setParent(new ConstantExpression('base', $token->getLine()), false);
        $this->parser->getStream()->expect(Token::BLOCK_END_TYPE);

        // returns a blank text node so the whole child body is a single removable node
        return new TextNode('   ', $token->getLine());
    }

    public function getTag(): string
    {
        return 'set_parent';
    }
}
