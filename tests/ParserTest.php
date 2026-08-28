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
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Twig\Environment;
use Twig\Error\SyntaxError;
use Twig\Lexer;
use Twig\Loader\ArrayLoader;
use Twig\Node\BlockNode;
use Twig\Node\BlockReferenceNode;
use Twig\Node\DoNode;
use Twig\Node\EmptyNode;
use Twig\Node\Expression\BlockReferenceExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\GetAttrExpression;
use Twig\Node\Expression\MacroReferenceExpression;
use Twig\Node\ForNode;
use Twig\Node\IfNode;
use Twig\Node\MacroDeclarationNode;
use Twig\Node\MacroNode;
use Twig\Node\Node;
use Twig\Node\Nodes;
use Twig\Node\PrintNode;
use Twig\Node\TextNode;
use Twig\NodeVisitor\NodeVisitorInterface;
use Twig\Parser;
use Twig\Source;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;
use Twig\TokenStream;

class ParserTest extends TestCase
{
    use ExpectDeprecationTrait;

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
            yield $target.' grouped static with parentheses' => ['('.$target.'.foo())'];
            yield $target.' dynamic with parentheses' => [$target.'.(name)()'];
            yield $target.' grouped dynamic with parentheses' => ['('.$target.'.(name)())'];
        }
    }

    /**
     * @dataProvider provideMacroTargetExpressionsWithoutParentheses
     *
     * @group legacy
     */
    #[DataProvider('provideMacroTargetExpressionsWithoutParentheses')]
    #[Group('legacy')]
    public function testMacroTargetsWithoutParenthesesAreDeprecated(string $expression): void
    {
        $twig = new Environment(new ArrayLoader());

        $this->expectDeprecation('Since twig/twig 3.29: Omitting parentheses when calling a macro is deprecated and will throw a SyntaxError in Twig 4.0; add parentheses after the macro name in "index" at line 1.');

        $module = $twig->parse($twig->tokenize(new Source("{% import _self as macros %}{{ $expression }}", 'index')));
        $macroReferences = [];
        $attributeExpressions = [];

        $this->collectExpressions($module, $macroReferences, $attributeExpressions);

        $this->assertCount(1, $macroReferences);
        $this->assertSame([], $attributeExpressions);
    }

    public static function provideMacroTargetExpressionsWithoutParentheses(): iterable
    {
        foreach (['_self', 'macros'] as $target) {
            yield $target.' static without parentheses' => [$target.'.foo'];
            yield $target.' grouped static without parentheses' => ['('.$target.'.foo)'];
            yield $target.' dynamic without parentheses' => [$target.'.(name)'];
            yield $target.' grouped dynamic without parentheses' => ['('.$target.'.(name))'];
        }
    }

    /**
     * @dataProvider provideMacroTargetExpressionsWithoutParentheses
     */
    #[DataProvider('provideMacroTargetExpressionsWithoutParentheses')]
    public function testMacroTargetsWithoutParenthesesAreAllowedInDefinedTest(string $expression): void
    {
        $twig = new Environment(new ArrayLoader());

        $module = $twig->parse($twig->tokenize(new Source("{% import _self as macros %}{{ $expression is defined }}{{ $expression is not defined }}", 'index')));
        $macroReferences = [];
        $attributeExpressions = [];

        $this->collectExpressions($module, $macroReferences, $attributeExpressions);

        $this->assertCount(2, $macroReferences);
        $this->assertSame([], $attributeExpressions);
    }

    /**
     * @dataProvider provideMacroTargetExpressions
     *
     * @group legacy
     */
    #[DataProvider('provideMacroTargetExpressions')]
    #[Group('legacy')]
    public function testMacroTargetsWithParenthesesAreDeprecatedInDefinedTest(string $expression): void
    {
        $twig = new Environment(new ArrayLoader());

        $this->expectDeprecation('Since twig/twig 3.29: Using parentheses when testing a macro with the "defined" test is deprecated and will throw a SyntaxError in Twig 4.0; remove the parentheses after the macro name in "index" at line 1.');

        $module = $twig->parse($twig->tokenize(new Source("{% import _self as macros %}{{ $expression is defined }}", 'index')));
        $macroReferences = [];
        $attributeExpressions = [];

        $this->collectExpressions($module, $macroReferences, $attributeExpressions);

        $this->assertCount(1, $macroReferences);
        $this->assertSame([], $attributeExpressions);
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

    public function testCleanupBodyAcceptsAnUnregisteredBlockReference(): void
    {
        $twig = new Environment(new ArrayLoader());
        $twig->addTokenParser(new UnregisteredBlockReferenceTokenParser());

        $node = $twig->parse($twig->tokenize(new Source('{% unregistered_block_reference %}', 'index')));

        $this->assertInstanceOf(EmptyNode::class, $node->getNode('body')->getNode('0'));
    }

    public function testDocumentationIsAttachedToNodes(): void
    {
        $twig = new Environment(new ArrayLoader(), ['autoescape' => false]);
        $module = $twig->parse($twig->tokenize(new Source(<<<'TWIG'
{## Printed expression #}
{{ answer }}
{## Conditional tag #}
{% if enabled %}Enabled{% endif %}
{## Do tag #}
{% do max() %}
{## Text node #}
Text
TWIG, 'index')));
        $body = $module->getNode('body')->getNode('0');

        $this->assertInstanceOf(PrintNode::class, $body->getNode('0'));
        $this->assertSame('Printed expression', $body->getNode('0')->getDocumentation());
        $this->assertNull($body->getNode('0')->getNode('expr')->getDocumentation());
        $this->assertInstanceOf(IfNode::class, $body->getNode('2'));
        $this->assertSame('Conditional tag', $body->getNode('2')->getDocumentation());
        $this->assertInstanceOf(DoNode::class, $body->getNode('3'));
        $this->assertSame('Do tag', $body->getNode('3')->getDocumentation());
        $this->assertInstanceOf(TextNode::class, $body->getNode('4'));
        $this->assertNull($body->getNode('4')->getDocumentation());
    }

    public function testEmptyCommentIsNotLexedAsDocumentation(): void
    {
        $twig = new Environment(new ArrayLoader(), ['autoescape' => false]);
        $module = $twig->parse($twig->tokenize(new Source(<<<'TWIG'
{##}{{ answer }}
{% block a %}x{##}{% endblock %}{% block b %}y{% endblock %}
TWIG, 'index')));
        $body = $module->getNode('body')->getNode('0');

        $this->assertInstanceOf(PrintNode::class, $body->getNode('0'));
        $this->assertNull($body->getNode('0')->getDocumentation());
    }

    public function testInlineDocumentationBeforeATagNameIsIgnored(): void
    {
        $twig = new Environment(new ArrayLoader(), ['autoescape' => false]);
        $module = $twig->parse($twig->tokenize(new Source("{% ## Not tag documentation\ndo max() %}", 'index')));

        $this->assertNull($module->getNode('body')->getNode('0')->getDocumentation());
    }

    public function testDocumentationIsAvailableToNodeVisitors(): void
    {
        $visitor = new DocumentationReadingNodeVisitor();
        $twig = new Environment(new ArrayLoader());
        $twig->addNodeVisitor($visitor);

        $twig->parse($twig->tokenize(new Source('{## Block documentation #}{% block content %}{% endblock %}{## Macro documentation #}{% macro input() %}{% endmacro %}', 'index')));

        $this->assertSame([
            BlockNode::class => 'Block documentation',
            MacroNode::class => 'Macro documentation',
        ], $visitor->documentation);
    }

    public function testCustomTokenParserCanSetDocumentationTarget(): void
    {
        $twig = new Environment(new ArrayLoader());
        $twig->addTokenParser(new DocumentationTargetTokenParser());

        $module = $twig->parse($twig->tokenize(new Source('{## Target documentation #}{% documentation_target %}', 'index')));
        $node = $module->getNode('body')->getNode('0');

        $this->assertNull($node->getDocumentation());
        $this->assertSame('Target documentation', $node->getNode('target')->getDocumentation());
    }

    public function testDocumentationIsRemovedFromUnsupportedOptimizedNodes(): void
    {
        $twig = new Environment(new ArrayLoader(), ['autoescape' => false]);
        $module = $twig->parse($twig->tokenize(new Source(<<<'TWIG'
{## Text node #}
{{ 'Hello' }}
{## Block reference #}
{{ block('content') }}
{% block content %}Content{% endblock %}
TWIG, 'index')));
        $body = $module->getNode('body')->getNode('0');

        $this->assertInstanceOf(TextNode::class, $body->getNode('0'));
        $this->assertNull($body->getNode('0')->getDocumentation());
        $this->assertInstanceOf(BlockReferenceExpression::class, $body->getNode('2'));
        $this->assertNull($body->getNode('2')->getDocumentation());
    }

    public function testDocumentationIsAttachedToAssignmentTargets(): void
    {
        $twig = new Environment(new ArrayLoader(), ['autoescape' => false, 'optimizations' => 0]);
        $module = $twig->parse($twig->tokenize(new Source(<<<'TWIG'
{% set ## First name
    first_name, ## Last name
    last_name = user.first_name, user.last_name
%}
{% for ## Product identifier
    product_id, ## Product
    product in products
%}{% endfor %}
TWIG, 'index')));
        $body = $module->getNode('body')->getNode('0');

        $this->assertSame('First name', $body->getNode('0')->getNode('names')->getNode('0')->getDocumentation());
        $this->assertSame('Last name', $body->getNode('0')->getNode('names')->getNode('1')->getDocumentation());

        $for = $body->getNode('1');
        $this->assertInstanceOf(ForNode::class, $for);
        $this->assertSame('Product identifier', $for->getNode('key_target')->getDocumentation());
        $this->assertSame('Product', $for->getNode('value_target')->getDocumentation());
    }

    public function testDocumentationIsAttachedToMacroNode(): void
    {
        $twig = new Environment(new ArrayLoader());
        $module = $twig->parse($twig->tokenize(new Source("{## Input macro #}\n{% macro input() %}{% endmacro %}", 'index')));

        $this->assertSame('Input macro', $module->getNode('macros')->getNode('input')->getDocumentation());

        $module = $twig->parse($twig->tokenize(new Source("{## Input macro #}\n{% macro input(## Name argument\nname, ## Value argument\nvalue) %}{% endmacro %}", 'index')));
        $declaration = $module->getNode('body')->getNode('0');
        $macro = $module->getNode('macros')->getNode('input');

        $this->assertNull($declaration->getDocumentation());
        $this->assertSame('Input macro', $macro->getDocumentation());
        $this->assertSame('Name argument', $macro->getNode('arguments')->getNode('0')->getDocumentation());
        $this->assertSame(3, $macro->getNode('arguments')->getNode('0')->getTemplateLine());
        $this->assertSame('Value argument', $macro->getNode('arguments')->getNode('2')->getDocumentation());
        $this->assertSame(4, $macro->getNode('arguments')->getNode('2')->getTemplateLine());
    }

    /**
     * @group legacy
     */
    #[Group('legacy')]
    public function testDuplicateMacroKeepsOnlyTheLastDocumentation(): void
    {
        $twig = new Environment(new ArrayLoader());

        $this->expectDeprecation('Since twig/twig 3.29: Defining the macro "input" more than once in "index" is deprecated and will throw a SyntaxError in Twig 4.0 (previous definition at line 1, new definition at line 1). The last definition is used in Twig 3.');

        $module = $twig->parse($twig->tokenize(new Source('{## First #}{% macro input() %}{% endmacro %}{## Second #}{% macro input() %}{% endmacro %}', 'index')));

        $this->assertSame('Second', $module->getNode('macros')->getNode('input')->getDocumentation());
        foreach ($module->getNode('body')->getNode('0') as $declaration) {
            $this->assertNull($declaration->getDocumentation());
        }
    }

    /**
     * @group legacy
     */
    #[Group('legacy')]
    public function testDuplicateMacroDeprecationsReferToPreviousDefinition(): void
    {
        $twig = new Environment(new ArrayLoader());

        $this->expectDeprecation('Since twig/twig 3.29: Defining the macro "input" more than once in "index" is deprecated and will throw a SyntaxError in Twig 4.0 (previous definition at line 1, new definition at line 2). The last definition is used in Twig 3.');
        $this->expectDeprecation('Since twig/twig 3.29: Defining the macro "input" more than once in "index" is deprecated and will throw a SyntaxError in Twig 4.0 (previous definition at line 2, new definition at line 3). The last definition is used in Twig 3.');

        $twig->parse($twig->tokenize(new Source("{% macro input() %}{% endmacro %}\n{% macro input() %}{% endmacro %}\n{% macro input() %}{% endmacro %}", 'index')));
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

class DocumentationReadingNodeVisitor implements NodeVisitorInterface
{
    public array $documentation = [];

    public function enterNode(Node $node, Environment $env): Node
    {
        if (($node instanceof BlockNode || $node instanceof MacroNode) && null !== $documentation = $node->getDocumentation()) {
            $this->documentation[$node::class] = $documentation;
        }

        return $node;
    }

    public function leaveNode(Node $node, Environment $env): ?Node
    {
        return $node;
    }

    public function getPriority(): int
    {
        return -1024;
    }
}

class DocumentationTargetTokenParser extends AbstractTokenParser
{
    public function parse(Token $token): Node
    {
        $this->parser->getStream()->expect(Token::BLOCK_END_TYPE);
        $target = new EmptyNode($token->getLine());
        $this->parser->setDocumentationTarget($target);

        return new Nodes(['target' => $target], $token->getLine());
    }

    public function getTag(): string
    {
        return 'documentation_target';
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

class UnregisteredBlockReferenceTokenParser extends AbstractTokenParser
{
    public function parse(Token $token): Node
    {
        $this->parser->setParent(new ConstantExpression('base', $token->getLine()), false);
        $this->parser->getStream()->expect(Token::BLOCK_END_TYPE);

        return new BlockReferenceNode('missing', $token->getLine());
    }

    public function getTag(): string
    {
        return 'unregistered_block_reference';
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
