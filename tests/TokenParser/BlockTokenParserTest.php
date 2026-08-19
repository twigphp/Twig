<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Tests\TokenParser;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Error\SyntaxError;
use Twig\Loader\ArrayLoader;
use Twig\Node\BlockNode;
use Twig\Node\BlockReferenceNode;
use Twig\Node\DoNode;
use Twig\Parser;
use Twig\Source;

class BlockTokenParserTest extends TestCase
{
    /** @dataProvider provideDocumentedBlocks */
    #[DataProvider('provideDocumentedBlocks')]
    public function testDocumentation(string $template, ?string $expected): void
    {
        $this->assertSame($expected, $this->parseBlock($template)->getDocumentation());
    }

    /** @dataProvider provideDocumentationWhitespaceControl */
    #[DataProvider('provideDocumentationWhitespaceControl')]
    public function testDocumentationWhitespaceControl(string $template, string $expectedOutput): void
    {
        $env = new Environment(new ArrayLoader(['index' => $template]), ['autoescape' => false, 'optimizations' => 0]);
        $module = $env->parse($env->tokenize(new Source($template, 'index')));

        $this->assertSame('The main content', $module->getNode('blocks')->getNode('content')->getNode('0')->getDocumentation());
        $this->assertSame($expectedOutput, $env->render('index'));
    }

    public static function provideDocumentationWhitespaceControl(): iterable
    {
        yield 'opening whitespace trim marker' => [
            "A \n{##- The main content #}\n{% block content %}B{% endblock %}",
            'AB',
        ];
        yield 'opening line whitespace trim marker' => [
            "A \n  {##~ The main content #}\n{% block content %}B{% endblock %}",
            "A \nB",
        ];
        yield 'closing whitespace trim marker' => [
            "A{## The main content -##}\n  {% block content %}B{% endblock %}",
            'AB',
        ];
        yield 'closing line whitespace trim marker' => [
            "A{## The main content ~##}  \n{% block content %}B{% endblock %}",
            "A\nB",
        ];
    }

    public function testDocumentationIsMovedFromTheBlockReference(): void
    {
        $env = new Environment(new ArrayLoader(), ['autoescape' => false, 'optimizations' => 0]);
        $module = $env->parse($env->tokenize(new Source("{## The main content #}\n{% block content %}Hello{% endblock %}", 'index')));
        $reference = $module->getNode('body')->getNode('0');

        $this->assertInstanceOf(BlockReferenceNode::class, $reference);
        $this->assertNull($reference->getDocumentation());
        $this->assertSame('The main content', $module->getNode('blocks')->getNode('content')->getNode('0')->getDocumentation());
    }

    public function testDocumentationIsPreservedInChildTemplates(): void
    {
        $env = new Environment(new ArrayLoader(), ['autoescape' => false, 'optimizations' => 0]);
        $module = $env->parse($env->tokenize(new Source("{% extends 'base' %}\n{## The main content #}\n{% block content %}Hello{% endblock %}", 'index')));

        $this->assertSame('The main content', $module->getNode('blocks')->getNode('content')->getNode('0')->getDocumentation());
    }

    public static function provideDocumentedBlocks(): iterable
    {
        yield 'documentation comment before block' => [
            "{## The main content #}\n{% block content %}Hello{% endblock %}",
            'The main content',
        ];
        yield 'symmetric closing marker' => [
            "{## The main content ##}\n{% block content %}Hello{% endblock %}",
            'The main content',
        ];
        yield 'multiple documentation comments' => [
            "{## The main content #}\n{## Displayed on every page #}\n{% block content %}Hello{% endblock %}",
            "The main content\nDisplayed on every page",
        ];
        yield 'empty documentation comment' => [
            "{## #}\n{% block content %}Hello{% endblock %}",
            null,
        ];
        yield 'zero documentation comment' => [
            "{## 0 #}\n{% block content %}Hello{% endblock %}",
            '0',
        ];
        yield 'regular comment' => [
            "{# The main content #}\n{% block content %}Hello{% endblock %}",
            null,
        ];
        yield 'unrelated documentation comment' => [
            "{## Not block documentation #}\nHello\n{% block content %}Hello{% endblock %}",
            null,
        ];
    }

    /**
     * @dataProvider provideDocumentationBoundaries
     */
    #[DataProvider('provideDocumentationBoundaries')]
    public function testDocumentationDoesNotCrossSpecialConstructs(string $template): void
    {
        $twig = new Environment(new ArrayLoader(), ['autoescape' => false, 'optimizations' => 0]);
        $module = $twig->parse($twig->tokenize(new Source($template, 'index')));
        $body = $module->getNode('body')->getNode('0');
        $node = $body instanceof DoNode ? $body : $body->getNode('1');

        $this->assertNull($node->getDocumentation());
    }

    public static function provideDocumentationBoundaries(): iterable
    {
        yield 'line tag' => ["{## Not do documentation #}\n{% line 10 %}{% do max() %}"];
        yield 'empty verbatim tag' => ["{## Not do documentation #}\n{% verbatim %}{% endverbatim %}{% do max() %}"];
        yield 'whitespace verbatim tag' => ["{## Not do documentation #}\n{% verbatim %} {% endverbatim %}{% do max() %}"];
    }

    public function testShortcutAssignmentNamedDocsRemainsValid(): void
    {
        $twig = new Environment(new ArrayLoader(['index' => '{% block title docs="The page title" %}']));

        $this->assertSame('The page title', $twig->render('index'));
    }

    public function testDocumentationCommentRequiresALineBreak(): void
    {
        $twig = new Environment(new ArrayLoader(['index' => '{% block ## Description %}Hello{% endblock %}']));

        $this->expectException(SyntaxError::class);

        $twig->render('index');
    }

    private function parseBlock(string $template): BlockNode
    {
        $env = new Environment(new ArrayLoader(), ['cache' => false, 'autoescape' => false]);
        $stream = $env->tokenize(new Source($template, ''));
        $parser = new Parser($env);

        return $parser->parse($stream)->getNode('blocks')->getNode('content')->getNode('0');
    }
}
