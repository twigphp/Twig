<?php

namespace Twig\Tests\TokenParser;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Parser;
use Twig\Source;

class TypesTokenParserTest extends TestCase
{
    /** @dataProvider getMappingTests */
    public function testMappingParsing(string $template, array $expected): void
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
        ];
    }
}
