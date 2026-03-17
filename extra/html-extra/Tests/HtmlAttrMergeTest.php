<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Extra\Html\Tests;

use PHPUnit\Framework\TestCase;
use Twig\Error\RuntimeError;
use Twig\Extra\Html\HtmlAttr\MergeableInterface;
use Twig\Extra\Html\HtmlExtension;

class HtmlAttrMergeTest extends TestCase
{
    /**
     * @dataProvider htmlAttrProvider
     */
    public function testMerge(array $expected, array $inputs)
    {
        $result = HtmlExtension::htmlAttrMerge(...$inputs);

        self::assertEquals($expected, $result);
    }

    public static function htmlAttrProvider(): \Generator
    {
        yield 'merging different attributes from two arrays' => [
            ['id' => 'some-id', 'label' => 'some-label'],
            [
                ['id' => 'some-id'],
                ['label' => 'some-label'],
            ],
        ];

        yield 'merging different attributes from three arrays' => [
            ['id' => 'some-id', 'label' => 'some-label', 'role' => 'main'],
            [
                ['id' => 'some-id'],
                ['label' => 'some-label'],
                ['role' => 'main'],
            ],
        ];

        yield 'merging different attributes from Traversables' => [
            ['id' => 'some-id', 'label' => 'some-label', 'role' => 'main'],
            [
                new \ArrayIterator(['id' => 'some-id']),
                new \ArrayIterator(['label' => 'some-label']),
                new \ArrayIterator(['role' => 'main']),
            ],
        ];

        yield 'later keys override previous ones' => [
            ['key' => 'other'],
            [
                ['key' => 'this'],
                ['key' => 'that'],
                ['key' => 'other'],
            ],
        ];

        yield 'later keys override previous ones - as before, but there is no magic in attribute names like "id" or "class"' => [
            ['class' => 'other'],
            [
                ['class' => 'this'],
                ['class' => 'that'],
                ['class' => 'other'],
            ],
        ];

        yield 'in "merge array" mode, array_merge semantics will override non-numerical keys, but combine numerical ones' => [
            ['something' => ['first' => 'baz', 'second' => 'bar', 0 => 'other', 1 => 'more']],
            [
                ['something' => ['first' => 'foo']],
                ['something' => ['second' => 'bar']],
                ['something' => ['first' => 'baz']],
                ['something' => ['other']],
                ['something' => ['more']],
            ],
        ];

        yield 'ignore empty arrays, null or false values passed as arguments' => [
            ['something' => 'foo'],
            [
                ['something' => 'foo'],
                [],
                null,
                false,
            ],
        ];

        yield 'there is no special handling for scalars like true, false or null' => [
            ['this' => true, 'that' => false, 'other' => null],
            [
                ['this' => true],
                ['that' => false],
                ['other' => null],
            ],
        ];

        yield 'inline style values with numerical keys are merely collected' => [
            ['style' => ['font-weight: light', 'color: green', 'font-weight: bold']],
            [
                ['style' => ['font-weight: light']],
                ['style' => ['color: green', 'font-weight: bold']],
            ],
        ];

        yield 'inline style values can be overridden when they use names (array keys)' => [
            ['style' => ['font-weight' => 'bold', 'color' => 'red']],
            [
                ['style' => ['font-weight' => 'light']],
                ['style' => ['color' => 'green', 'font-weight' => 'bold']],
                ['style' => ['color' => 'red']],
            ],
        ];

        yield 'no merging happens when mixing numerically indexed inline styles with named ones' => [
            ['style' => ['color: green', 'color' => 'red']],
            [
                ['style' => ['color: green']],
                ['style' => ['color' => 'red']],
            ],
        ];

        // MergeableInterface
        yield 'MergeableInterface mergeInto is called when new value implements interface' => [
            ['class' => 'merged: old + new'],
            [
                ['class' => 'old'],
                ['class' => new MergeableStub('new')],
            ],
        ];

        yield 'MergeableInterface appendFrom is called when existing value implements interface' => [
            ['class' => 'appended: old + new'],
            [
                ['class' => new MergeableStub('old')],
                ['class' => 'new'],
            ],
        ];

        yield 'MergeableInterface mergeInto is called when both implement interface' => [
            ['class' => 'merged: value1 + value2'],
            [
                ['class' => new MergeableStub('value1')],
                ['class' => new MergeableStub('value2')],
            ],
        ];

        yield 'MergeableInterface with array value' => [
            ['class' => 'appended: base + extra1, extra2'],
            [
                ['class' => new MergeableStub('base')],
                ['class' => ['extra1', 'extra2']],
            ],
        ];

        // Scalar and object merging
        yield 'string replaces object' => [
            ['value' => 'new-string'],
            [
                ['value' => new \stdClass()],
                ['value' => 'new-string'],
            ],
        ];

        yield 'object replaces string' => [
            ['value' => new \stdClass()],
            [
                ['value' => 'old-string'],
                ['value' => new \stdClass()],
            ],
        ];
    }

    public function testIncompatibleValuesMergeThrowsException()
    {
        $this->expectException(RuntimeError::class);
        $this->expectExceptionMessage('Cannot merge incompatible values for key "test"');

        HtmlExtension::htmlAttrMerge(
            ['test' => ['array']],
            ['test' => 'scalar']
        );
    }
}

class MergeableStub implements MergeableInterface
{
    public function __construct(private readonly mixed $value)
    {
    }

    public function mergeInto(mixed $previous): mixed
    {
        $previousValue = $previous instanceof self ? $previous->value : $previous;

        return new self("merged: {$previousValue} + {$this->value}");
    }

    public function appendFrom(mixed $newValue): mixed
    {
        if (\is_array($newValue)) {
            $newValue = implode(', ', $newValue);
        } elseif ($newValue instanceof self) {
            $newValue = $newValue->value;
        }

        return new self("appended: {$this->value} + {$newValue}");
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
