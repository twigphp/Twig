<?php

namespace Twig\Extra\Html\Tests;

use PHPUnit\Framework\TestCase;
use Twig\Error\RuntimeError;
use Twig\Extra\Html\HtmlAttributes;

class HtmlAttributesTest extends TestCase
{
    /**
     * @dataProvider htmlAttrProvider
     * @throws RuntimeError
     */
    public function testMerge(array $input, array $expected)
    {
        $result = HtmlAttributes::merge(...$input);
        self::assertSame($expected, $result);
    }

    public function testNonIterableAttributeValuesThrowException()
    {
        $this->expectException(\Twig\Error\RuntimeError::class);
        $result = HtmlAttributes::merge(['class' => 'a'], 'b');
    }

    /**
     * Tests output of HtmlAttributes::merge() method can be used as an array of attributes.
     * @return void
     * @throws RuntimeError
     */
    public function testMultipleMerge()
    {
        $result1 = HtmlAttributes::merge(['a' => 'b', 'c' => 'd'],
            true ? ['e' => 'f'] : null,
            false ? ['g' => 'h'] : null,
            ['i' => true],
            ['j' => true],
            ['j' => false],
            ['k' => true],
            ['k' => null]
        );

        $result2 = HtmlAttributes::merge(
            ['class' => 'a b j'],
            ['class' => ['c', 'd', 'e f']],
            ['class' => ['g' => true, 'h' => false, 'i' => true]],
            ['class' => ['h' => true]],
            ['class' => ['i' => false]],
            ['class' => ['j' => null]],
        );

        $result = HtmlAttributes::merge($result1, $result2);

        self::assertSame([
            'a' => 'b',
            'c' => 'd',
            'e' => 'f',
            'i' => true,
            'j' => false,
            'k' => null,
            'class' => [
                'a' => true,
                'b' => true,
                'j' => null,
                'c' => true,
                'd' => true,
                'e' => true,
                'f' => true,
                'g' => true,
                'h' => true,
                'i' => false,
            ]
        ], $result);
    }


    public function htmlAttrProvider(): \Generator
    {
        yield 'merging basic attributes' => [
            [
                ['a' => 'b', 'c' => 'd'],
                true ? ['e' => 'f'] : null,
                false ? ['g' => 'h'] : null,
                ['i' => true],
                ['j' => true],
                ['j' => false],
                ['k' => true],
                ['k' => null],
            ],
            [
                'a' => 'b',
                'c' => 'd',
                'e' => 'f',
                'i' => true,
                'j' => false,
                'k' => null
            ],
        ];

        /**
         * class attributes are merged into an array so they can be concatenated in later processing.
         */
        yield 'merging class attributes' => [
            [
                ['class' => 'a b j'],
                ['class' => ['c', 'd', 'e f']],
                ['class' => ['g' => true, 'h' => false, 'i' => true]],
                ['class' => ['h' => true]],
                ['class' => ['i' => false]],
                ['class' => ['j' => null]],
            ],
            ['class' => [
                'a' => true,
                'b' => true,
                'j' => null,
                'c' => true,
                'd' => true,
                'e' => true,
                'f' => true,
                'g' => true,
                'h' => true,
                'i' => false,
            ]],
        ];

        /**
         * style attributes are merged into an array so they can be concatenated in later processing.
         * style strings are split into key, value pairs eg. 'color: red' becomes ['color' => 'red']
         * style attributes which are arrays with false and null values are also processed
         * false and null values override string values eg. ['display: block' => false] becomes ['display' => false]
         */
        yield 'merging style attributes' => [
            [
                ['style' => 'a: b;'],
                ['style' => ['c' => 'd', 'e' => 'f']],
                ['style' => ['g: h;']],
                ['style' => [
                    'i: j; k: l' => true,
                    'm: n' => false,
                    'o: p' => null
                ]],
            ],
            ['style' => [
                'a' => 'b',
                'c' => 'd',
                'e' => 'f',
                'g' => 'h',
                'i' => 'j',
                'k' => 'l',
                'm' => false,
                'o' => null,
            ]],
        ];

        /**
         * `data` arrays are expanded into `data-*` attributes before further processing.
         */
        yield 'merging data-* attributes' => [
            [
                ['data-a' => 'a'],
                ['data-b' => 'b'],
                ['data-c' => true],
                ['data-d' => false],
                ['data-e' => null],
                ['data-f' => ['a' => 'b']],
                ['data' => ['g' => 'g', 'h' => true]],
                ['data-h' => false],
                ['data-h' => 'h'],
            ],
            [
                'data-a' => 'a',
                'data-b' => 'b',
                'data-c' => true,
                'data-d' => false,
                'data-e' => null,
                'data-f' => ['a' => 'b'],
                'data-g' => 'g',
                'data-h' => 'h',
            ],
        ];

        /**
         * `aria` arrays are expanded into `aria-*` attributes before further processing.
         */
        yield 'merging aria-* attributes' => [
            [
                ['aria-a' => 'a'],
                ['aria-b' => 'b'],
                ['aria-c' => true],
                ['aria-d' => false],
                ['aria-e' => null],
                ['aria-f' => ['a' => 'b']],
                ['aria' => ['g' => 'g', 'h' => true]],
                ['aria-h' => false],
                ['aria-h' => 'h'],
            ],
            [
                'aria-a' => 'a',
                'aria-b' => 'b',
                'aria-c' => true,
                'aria-d' => false,
                'aria-e' => null,
                'aria-f' => ['a' => 'b'],
                'aria-g' => 'g',
                'aria-h' => 'h',
            ],
        ];

        yield 'merging data-controller attributes' => [
            [
                ['data' => ['controller' => 'c1 c2']],
                ['data-controller' => 'c3'],
                ['data-controller' => ['c4' => true]],
                ['data-controller' => ['c5' => false]],
            ],
            [
                'data-controller' => [
                    'c1' => true,
                    'c2' => true,
                    'c3' => true,
                    'c4' => true,
                    'c5' => false
                ],
            ],
        ];


    }
}
