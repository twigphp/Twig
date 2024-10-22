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
    public function testMerge(array $attributes, array $expectedMergedAttributes, string $expectedAttributeString)
    {
        $mergedAttributes = HtmlAttributes::merge(...$attributes);
        self::assertSame($expectedMergedAttributes, $mergedAttributes);
        $attributeString = HtmlAttributes::renderAttributes($mergedAttributes);
        self::assertSame($attributeString, $expectedAttributeString);
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
            'a="b" c="d" e="f" i',
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
            'class="a b c d e f g h"',
        ];

        /**
         * style attributes are merged into an array so they can be concatenated in later processing.
         * // Strings are true by default.
         * `HtmlAttributes::merge(['color: red']) === ['color: red' => true]`
         * // Arrays have a boolean / null value
         * `HtmlAttributes::merge(['color: red' => true ]) === ['color: red' => true]`
         * `HtmlAttributes::merge(['color: red' => false ]) === ['color: red' => false]`
         * String values are split into key value pairs and then processed
         * `HtmlAttributes::merge(['color: red; background: blue']) === ['color: red' => true, 'background: blue' => true]`
         * `HtmlAttributes::merge(['color: red; background: blue' => true]) === ['color: red' => true, 'background: blue' => true]`
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
                'a: b;' => true,
                'c: d;' => true,
                'e: f;' => true,
                'g: h;' => true,
                'i: j;' => true,
                'k: l;' => true,
                'm: n;' => false,
                'o: p;' => null,
            ]],
            'style="a: b; c: d; e: f; g: h; i: j; k: l;"',
        ];

        /**
         * `data` arrays are expanded into `data-*` attributes before further processing.
         */
        yield 'merging data-* attributes' => [
            [
                ['data-string' => 'a'],
                ['data-int' => 100],
                ['data-bool-true' => true],
                ['data-bool-false' => false],
                ['data-null' => null],
                ['data-array' => ['a' => 'b']],
                ['data' => ['expanded-0' => true, 'expanded-1' => false, 'expanded-2' => null]],
            ],
            [
                'data-string' => 'a',
                'data-int' => 100,
                'data-bool-true' => true,
                'data-bool-false' => false,
                'data-null' => null,
                'data-array' => ['a' => 'b'],
                'data-expanded-0' => true,
                'data-expanded-1' => false,
                'data-expanded-2' => null,
            ],
            'data-string="a" data-int="100" data-bool-true data-array="{&quot;a&quot;:&quot;b&quot;}" data-expanded-0'
        ];

        /**
         * `aria` arrays are expanded into `aria-*` attributes before further processing.
         */
        yield 'merging aria-* attributes' => [
            [
                ['aria-string' => 'a'],
                ['aria-int' => 100],
                ['aria-bool-true' => true],
                ['aria-bool-false' => false],
                ['aria-null' => null],
                ['aria-array' => ['a', 'b']],
                ['aria' => ['expanded-0' => true, 'expanded-1' => false, 'expanded-2' => null]],
            ],
            [
                'aria-string' => 'a',
                'aria-int' => 100,
                'aria-bool-true' => true,
                'aria-bool-false' => false,
                'aria-null' => null,
                'aria-array' => ['a', 'b'],
                'aria-expanded-0' => true,
                'aria-expanded-1' => false,
                'aria-expanded-2' => null,
            ],
            'aria-string="a" aria-int="100" aria-bool-true="true" aria-bool-false="false" aria-array="a b" aria-expanded-0="true" aria-expanded-1="false"'
        ];

        yield 'merging data-controller attributes' => [
            [
                ['data' => ['controller' => 'c1 c2']],
                ['data-controller' => 'c3'],
                ['data-controller' => ['c4' => true]],
                ['data-controller' => ['c5' => false]],
                ['data-controller' => ['c6' => null]],
            ],
            [
                'data-controller' => [
                    'c1' => true,
                    'c2' => true,
                    'c3' => true,
                    'c4' => true,
                    'c5' => false,
                    'c6' => null
                ],
            ],
            'data-controller="c1 c2 c3 c4"'
        ];


    }
}
