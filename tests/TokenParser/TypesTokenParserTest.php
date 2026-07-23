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

        self::assertEquals($expected, $typesNode->getAttribute('mapping'));
    }

    public function testDocsOptionRequiresAnEqualSign(): void
    {
        $env = new Environment(new ArrayLoader(), ['cache' => false, 'autoescape' => false]);
        $stream = $env->tokenize(new Source('{% types {foo: "bar" docs "Some description"} %}', ''));
        $parser = new Parser($env);

        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage('The "docs" option must be followed by an equal sign (=)');

        $parser->parse($stream);
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

            // docs option
            [
                '{% types {foo: "bar" docs="Some description"} %}',
                [
                    'foo' => ['type' => 'bar', 'optional' => false, 'docs' => 'Some description'],
                ],
            ],

            // docs option on an optional name, with a trailing comma
            [
                '{% types {foo?: "bar" docs="Some description",} %}',
                [
                    'foo' => ['type' => 'bar', 'optional' => true, 'docs' => 'Some description'],
                ],
            ],

            // docs option on some entries only, without {} enclosing
            [
                '{% types foo: "foo" docs="Some description", bar: "bar" %}',
                [
                    'foo' => ['type' => 'foo', 'optional' => false, 'docs' => 'Some description'],
                    'bar' => ['type' => 'bar', 'optional' => false, 'docs' => null],
                ],
            ],
        ];
    }
}
