<?php

namespace Twig\Extra\Html\Tests;

use PHPUnit\Framework\TestCase;
use Twig\Extra\Html\HtmlExtension;

class HtmlAttrMergeTest extends TestCase
{
    /**
     * @dataProvider htmlAttrProvider
     */
    public function testMerge(array $expected, array $inputs)
    {
        $result = HtmlExtension::htmlAttrMerge(...$inputs);

        self::assertSame($expected, $result);
    }

    public function htmlAttrProvider(): \Generator
    {
        yield 'merging different attributes from two arrays' => [
            ['id' => 'some-id', 'label' => 'some-label'],
            [
                ['id' => 'some-id'],
                ['label' => 'some-label'],
            ]
        ];

        yield 'merging different attributes from three arrays' => [
            ['id' => 'some-id', 'label' => 'some-label', 'role' => 'main'],
            [
                ['id' => 'some-id'],
                ['label' => 'some-label'],
                ['role' => 'main'],
            ]
        ];

        yield 'merging different attributes from Traversables' => [
            ['id' => 'some-id', 'label' => 'some-label', 'role' => 'main'],
            [
                new \ArrayIterator(['id' => 'some-id']),
                new \ArrayIterator(['label' => 'some-label']),
                new \ArrayIterator(['role' => 'main']),
            ]
        ];

        yield 'later keys override previous ones' => [
            ['id' => 'other'],
            [
                ['id' => 'this'],
                ['id' => 'that'],
                ['id' => 'other'],
            ]
        ];

        yield 'ignore empty strings or arrays passed as arguments' => [
            ['some' => 'attribute'],
            [
                ['some' => 'attribute'],
                [], // empty array
                '', // empty string
            ]
        ];

        yield 'keep "true" and "false" boolean values' => [
            ['disabled' => true, 'enabled' => false],
            [
                ['disabled' => true],
                ['enabled' => false],
            ]
        ];

        yield 'consolidate values for the "class" key' => [
            ['class' => ['foo', 'bar', 'baz']],
            [
                ['class' => ['foo']],
                ['class' => 'bar'], // string, not array
                ['class' => ['baz']],
            ]
        ];

        yield 'class values can be overridden when they use names (array keys)' => [
            ['class' => ['foo', 'bar', 'importance' => 'high']],
            [
                ['class' => 'foo'],
                ['class' => ['bar', 'importance' => 'low']],
                ['class' => ['importance' => 'high']],
            ]
        ];

        yield 'inline style values with numerical keys are merely collected' => [
            ['style' => ['font-weight: light', 'color: green', 'font-weight: bold']],
            [
                ['style' => ['font-weight: light']],
                ['style' => ['color: green', 'font-weight: bold']],
            ]
        ];

        yield 'inline style values can be overridden when they use names (array keys)' => [
            ['style' => ['font-weight' => 'bold', 'color' => 'red']],
            [
                ['style' => ['font-weight' => 'light']],
                ['style' => ['color' => 'green', 'font-weight' => 'bold']],
                ['style' => ['color' => 'red']],
            ]
        ];

        yield 'no merging happens when mixing numerically indexed inline styles with named ones' => [
            ['style' => ['color: green', 'color' => 'red']],
            [
                ['style' => ['color: green']],
                ['style' => ['color' => 'red']],
            ]
        ];

        yield 'turning aria attributes from array to flat keys' => [
            ['aria-role' => 'banner'],
            [
                ['aria' => ['role' => 'main']],
                ['aria' => ['role' => 'banner']],
            ]
        ];

        yield 'using aria attributes from a sub-array' => [
            ['aria-role' => 'main', 'aria-label' => 'none'],
            [
                ['aria' => ['role' => 'main', 'label' => 'none']],
            ]
        ];

        yield 'merging aria attributes, where the array values overrides the flat one' => [
            ['aria-role' => 'navigation'],
            [
                ['aria-role' => 'main'],
                ['aria' => ['role' => 'banner']],
                ['aria' => ['role' => 'navigation']],
            ]
        ];

        yield 'merging aria attributes, where the flat ones overrides the array' => [
            ['aria-role' => 'navigation'],
            [
                ['aria' => ['role' => 'main']],
                ['aria-role' => 'banner'],
                ['aria-role' => 'navigation'],
            ]
        ];

        yield 'using data attributes in a sub-array' => [
            ['data-foo' => 'this', 'data-bar' => 'that'],
            [
                ['data' => ['foo' => 'this']],
                ['data' => ['bar' => 'that']],
            ]
        ];

        yield 'turning data attributes from array to flat keys' => [
            ['data-test' => 'bar'],
            [
                ['data' => ['test' => 'foo']],
                ['data' => ['test' => 'bar']],
            ]
        ];

        yield 'merging data attributes, where the array values overrides the flat one' => [
            ['data-test' => 'baz'],
            [
                ['data-test' => 'foo'],
                ['data' => ['test' => 'bar']],
                ['data' => ['test' => 'baz']],
            ]
        ];

        yield 'merging data attributes, where the flat ones overrides the array' => [
            ['data-test' => 'baz'],
            [
                ['data' => ['test' => 'foo']],
                ['data-test' => 'bar'],
                ['data-test' => 'baz'],
            ]
        ];
    }
}
