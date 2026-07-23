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

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Node\BlockNode;
use Twig\Parser;
use Twig\Source;

class BlockTokenParserTest extends TestCase
{
    public function testDocsOption(): void
    {
        $block = $this->parseBlock('{% block content docs="The main content" %}Hello{% endblock %}', 'content');

        $this->assertSame('The main content', $block->getAttribute('docs'));
    }

    public function testDocsOptionWithTheShortcutSyntax(): void
    {
        $block = $this->parseBlock('{% block title docs="The page title" name %}', 'title');

        $this->assertSame('The page title', $block->getAttribute('docs'));
    }

    public function testDocsDefaultsToNull(): void
    {
        $block = $this->parseBlock('{% block content %}Hello{% endblock %}', 'content');

        $this->assertNull($block->getAttribute('docs'));
    }

    public function testShortcutSyntaxWithAVariableNamedDocs(): void
    {
        $twig = new Environment(new ArrayLoader(['index' => '{% block title docs %}']));

        $this->assertSame('Hello', $twig->render('index', ['docs' => 'Hello']));
    }

    private function parseBlock(string $template, string $name): BlockNode
    {
        $env = new Environment(new ArrayLoader(), ['cache' => false, 'autoescape' => false]);
        $stream = $env->tokenize(new Source($template, ''));
        $parser = new Parser($env);

        return $parser->parse($stream)->getNode('blocks')->getNode($name)->getNode('0');
    }
}
