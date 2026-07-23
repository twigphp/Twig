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
use Twig\Loader\ArrayLoader;
use Twig\Parser;
use Twig\Source;

class TypesTokenParserTest extends TestCase
{
    /** @dataProvider getMappingTests */
    #[DataProvider('getMappingTests')]
    public function testMappingParsing(string $template, array $expected): void
    {
        $env = new Environment(new ArrayLoader(), ['cache' => false, 'autoescape' => false]);
        $stream = $env->tokenize(new Source($template, ''));
        $parser = new Parser($env);

        $typesNode = $parser->parse($stream)->getNode('body')->getNode('0');

        self::assertSame($expected, $typesNode->getAttribute('mapping'));
    }

    public function testDocumentationIsAttachedToTypeNodes(): void
    {
        $env = new Environment(new ArrayLoader(), ['cache' => false, 'autoescape' => false]);
        $stream = $env->tokenize(new Source("{% types {\n## The foo variable\nfoo: 'foo',\n## The bar variable\nbar?: 'bar',\n} %}", ''));
        $parser = new Parser($env);

        $typesNode = $parser->parse($stream)->getNode('body')->getNode('0');

        self::assertSame('The foo variable', $typesNode->getNode('foo')->getDocumentation());
        self::assertSame(3, $typesNode->getNode('foo')->getTemplateLine());
        self::assertSame('The bar variable', $typesNode->getNode('bar')->getDocumentation());
        self::assertSame(5, $typesNode->getNode('bar')->getTemplateLine());
    }

    public static function getMappingTests(): array
    {
        return [
            // empty mapping
            [
                '{% types {} %}',
                [],
            ],

            // simple
            [
                '{% types {foo: "bar"} %}',
                [
                    'foo' => ['type' => 'bar', 'optional' => false],
                ],
            ],

            // trailing comma
            [
                '{% types {foo: "bar",} %}',
                [
                    'foo' => ['type' => 'bar', 'optional' => false],
                ],
            ],

            // optional name
            [
                '{% types {foo?: "bar"} %}',
                [
                    'foo' => ['type' => 'bar', 'optional' => true],
                ],
            ],

            // multiple pairs, duplicate values
            [
                '{% types {foo: "foo", bar?: "foo", baz: "baz"} %}',
                [
                    'foo' => ['type' => 'foo', 'optional' => false],
                    'bar' => ['type' => 'foo', 'optional' => true],
                    'baz' => ['type' => 'baz', 'optional' => false],
                ],
            ],

            // without {} enclosing
            [
                '{% types foo: "foo", bar: "bar" %}',
                [
                    'foo' => ['type' => 'foo', 'optional' => false],
                    'bar' => ['type' => 'bar', 'optional' => false],
                ],
            ],

            // documentation comments before types
            [
                "{% types {\n## The foo variable\n## Used by the header\nfoo: \"bar\"\n} %}",
                [
                    'foo' => [
                        'type' => 'bar',
                        'optional' => false,
                    ],
                ],
            ],

            // inline documentation comments
            [
                "{% types {\n## The foo variable\nfoo: \"foo\",\n## The bar variable\nbar?: \"bar\",\n} %}",
                [
                    'foo' => ['type' => 'foo', 'optional' => false],
                    'bar' => ['type' => 'bar', 'optional' => true],
                ],
            ],

            // regular comments
            [
                "{% types {\n# Not documentation\nfoo: \"bar\",\n} %}",
                [
                    'foo' => ['type' => 'bar', 'optional' => false],
                ],
            ],
        ];
    }
}
