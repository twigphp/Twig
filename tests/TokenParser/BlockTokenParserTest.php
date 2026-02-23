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
use Twig\Parser;
use Twig\Source;

class BlockTokenParserTest extends TestCase
{
    /** @dataProvider getBlockTests */
    public function testBlockParsing(string $template, string $blockName, ?string $expectedDocs)
    {
        $env = new Environment(new ArrayLoader(), ['cache' => false, 'autoescape' => false]);
        $stream = $env->tokenize(new Source($template, ''));
        $parser = new Parser($env);

        $blockNode = $parser->parse($stream)->getNode('blocks')->getNode($blockName)->getNode('0');

        if (null === $expectedDocs) {
            self::assertFalse($blockNode->hasAttribute('docs'));
        } else {
            self::assertEquals($expectedDocs, $blockNode->getAttribute('docs'));
        }
    }

    public static function getBlockTests(): array
    {
        return [
            // block without docs
            [
                'template' => '{% block content %}foo{% endblock %}',
                'blockName' => 'content',
                'expectedDocs' => null,
            ],

            // block with docs
            [
                'template' => '{% block content docs="The main content block" %}foo{% endblock %}',
                'blockName' => 'content',
                'expectedDocs' => 'The main content block',
            ],

            // shorthand block without docs
            [
                'template' => '{% block title "Hello" %}',
                'blockName' => 'title',
                'expectedDocs' => null,
            ],

            // shorthand block with docs
            [
                'template' => '{% block title docs="The page title" "Hello" %}',
                'blockName' => 'title',
                'expectedDocs' => 'The page title',
            ],
        ];
    }
}
