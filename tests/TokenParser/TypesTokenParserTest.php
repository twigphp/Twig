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

class TypesTokenParserTest extends TestCase
{
    /** @dataProvider getMappingTests */
    public function testMappingParsing(string $template, array $expected)
    {
        $env = new Environment(new ArrayLoader(), ['cache' => false, 'autoescape' => false]);
        $stream = $env->tokenize(new Source($template, ''));
        $parser = new Parser($env);

        $typesNode = $parser->parse($stream)->getNode('body')->getNode('0');

        self::assertEquals($expected, $typesNode->getAttribute('mapping'));
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
                    'foo' => ['type' => 'bar', 'optional' => false, 'docs' => null],
                ],
            ],

            // trailing comma
            [
                '{% types {foo: "bar",} %}',
                [
                    'foo' => ['type' => 'bar', 'optional' => false, 'docs' => null],
                ],
            ],

            // optional name
            [
                '{% types {foo?: "bar"} %}',
                [
                    'foo' => ['type' => 'bar', 'optional' => true, 'docs' => null],
                ],
            ],

            // multiple pairs, duplicate values
            [
                '{% types {foo: "foo", bar?: "foo", baz: "baz"} %}',
                [
                    'foo' => ['type' => 'foo', 'optional' => false, 'docs' => null],
                    'bar' => ['type' => 'foo', 'optional' => true, 'docs' => null],
                    'baz' => ['type' => 'baz', 'optional' => false, 'docs' => null],
                ],
            ],

            // without {} enclosing
            [
                '{% types foo: "foo", bar: "bar" %}',
                [
                    'foo' => ['type' => 'foo', 'optional' => false, 'docs' => null],
                    'bar' => ['type' => 'bar', 'optional' => false, 'docs' => null],
                ],
            ],

            // with docs attribute
            [
                '{% types {foo: "string" docs="The foo description"} %}',
                [
                    'foo' => ['type' => 'string', 'optional' => false, 'docs' => 'The foo description'],
                ],
            ],

            // with docs attribute and optional
            [
                '{% types {foo?: "string" docs="The foo description"} %}',
                [
                    'foo' => ['type' => 'string', 'optional' => true, 'docs' => 'The foo description'],
                ],
            ],

            // multiple entries with docs
            [
                '{% types {id: "string" docs="Unique identifier", multiple?: "boolean" docs="Allow multiple", value: "mixed"} %}',
                [
                    'id' => ['type' => 'string', 'optional' => false, 'docs' => 'Unique identifier'],
                    'multiple' => ['type' => 'boolean', 'optional' => true, 'docs' => 'Allow multiple'],
                    'value' => ['type' => 'mixed', 'optional' => false, 'docs' => null],
                ],
            ],

            // without {} enclosing with docs
            [
                '{% types foo: "foo" docs="Foo docs", bar: "bar" %}',
                [
                    'foo' => ['type' => 'foo', 'optional' => false, 'docs' => 'Foo docs'],
                    'bar' => ['type' => 'bar', 'optional' => false, 'docs' => null],
                ],
            ],
        ];
    }
}
