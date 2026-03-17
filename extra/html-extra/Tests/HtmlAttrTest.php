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
use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extra\Html\HtmlAttr\AttributeValueInterface;
use Twig\Extra\Html\HtmlAttr\SeparatedTokenList;
use Twig\Extra\Html\HtmlExtension;
use Twig\Loader\ArrayLoader;

class HtmlAttrTest extends TestCase
{
    /**
     * @dataProvider htmlAttrProvider
     */
    public function testPrintingAttributes(string $expected, array $inputs)
    {
        $result = HtmlExtension::htmlAttr(new Environment(new ArrayLoader()), ...$inputs);

        self::assertEquals($expected, $result);
    }

    public static function htmlAttrProvider(): \Generator
    {
        yield 'merging from variadic arguments; ignoring null, false and empty string values' => [
            'id="some-id" label="some-label" role="main"',
            [
                ['id' => 'some-id'],
                null,
                '',
                false,
                ['label' => 'some-label'],
                ['role' => 'main'],
            ],
        ];

        // Boolean attribute handling
        yield 'boolean true renders as empty string, except for aria-* and data-* it uses "true"' => [
            'required="" aria-disabled="true" data-yes="true"',
            [
                ['required' => true, 'aria-disabled' => true, 'data-yes' => true],
            ],
        ];

        yield 'boolean false omits attribute, except for aria-* it uses "false"' => [
            'aria-disabled="false"',
            [
                ['disabled' => false, 'aria-disabled' => false, 'data-gone' => false],
            ],
        ];

        yield 'null value omits attribute, also for special cases' => [
            '',
            [
                ['title' => null, 'style' => null, 'aria-gone' => null, 'data-nil' => null],
            ],
        ];

        yield 'empty string renders as empty attribute value' => [
            'title=""',
            [
                ['title' => ''],
            ],
        ];

        // Data attributes
        yield 'data attribute with array is JSON encoded' => [
            'data-config="{&quot;theme&quot;:&quot;dark&quot;}"',
            [
                ['data-config' => ['theme' => 'dark']],
            ],
        ];

        // In general, array values are printed as space-separated token lists
        yield 'array value renders as space-separated token list' => [
            'class="btn btn-primary btn-lg"',
            [
                ['class' => ['btn', 'btn-primary', 'btn-lg']],
            ],
        ];

        yield 'arrays with just an empty string produce the empty attribute' => [
            'foo=""',
            [
                ['foo' => ['']],
            ],
        ];

        yield 'arrays with just a true value produce the empty attribute' => [
            'foo=""',
            [
                ['foo' => [true]],
            ],
        ];

        yield 'arrays with just a null value are not printed' => [
            '',
            [
                ['foo' => [null]],
            ],
        ];

        // Style attributes
        yield 'style with plain string value' => [
            'style="color: red;"',
            [
                ['style' => 'color: red;'],
            ],
        ];

        yield 'style with associative array' => [
            'style="color: red; font-size: 16px;"',
            [
                ['style' => ['color' => 'red', 'font-size' => '16px']],
            ],
        ];

        yield 'style with numeric array' => [
            'style="color: red; font-size: 16px;"',
            [
                ['style' => ['color: red', 'font-size: 16px']],
            ],
        ];

        yield 'merging style attributes overrides by key' => [
            'style="color: blue; font-size: 14px;"',
            [
                ['style' => ['color' => 'red', 'font-size' => '14px']],
                ['style' => ['color' => 'blue']],
            ],
        ];

        // Escaping
        yield 'attribute name is escaped' => [
            'data-user&#x20;id="123"',
            [
                ['data-user id' => '123'],
            ],
        ];

        yield 'attribute value is escaped' => [
            'title="&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;"',
            [
                ['title' => '<script>alert("xss")</script>'],
            ],
        ];

        // Variadic merging scenarios
        yield 'scalar value overrides from left to right' => [
            'id="final"',
            [
                ['id' => 'first'],
                ['id' => 'second'],
                ['id' => 'final'],
            ],
        ];

        yield 'variadic with mixed false and null values' => [
            'id="test"',
            [
                ['id' => 'test'],
                null,
                false,
                null,
            ],
        ];

        yield 'variadic with empty arrays' => [
            'id="test"',
            [
                [],
                ['id' => 'test'],
                [],
            ],
        ];

        yield 'variadic with empty string values' => [
            'id="test"',
            [
                '',
                ['id' => 'test'],
                '',
            ],
        ];

        // AttributeValueInterface
        yield 'AttributeValueInterface with string value' => [
            'custom="custom-value"',
            [
                ['custom' => new AttributeValueStub('custom-value')],
            ],
        ];

        yield 'AttributeValueInterface with null value omits attribute' => [
            '',
            [
                ['custom' => new AttributeValueStub(null)],
            ],
        ];

        yield 'AttributeValueInterface wins over special case handling for style and data-*' => [
            'style="some style" data-custom="not JSON"',
            [
                ['style' => new AttributeValueStub('some style'), 'data-custom' => new AttributeValueStub('not JSON')],
            ],
        ];

        // Edge cases
        yield 'numeric attribute value' => [
            'tabindex="0"',
            [
                ['tabindex' => 0],
            ],
        ];

        yield 'zero is not treated as falsy' => [
            'data-count="0"',
            [
                ['data-count' => 0],
            ],
        ];

        // Scalar and object merging in rendering
        yield 'string replaces object in rendering' => [
            'value="new-string"',
            [
                ['value' => new \stdClass()],
                ['value' => 'new-string'],
            ],
        ];

        yield 'object replaces string in rendering uses __toString if available' => [
            'value="stringable-object"',
            [
                ['value' => 'old-string'],
                ['value' => new StringableStub('stringable-object')],
            ],
        ];
    }

    public function testIterableObjectCastedToArray()
    {
        /*
            This test case demonstrates how objects could e. g. implement helper logic
            to construct more complex attribute combinations and sets, and be passed as
            one argument to html_attr as well.
        */
        $object = new class implements \IteratorAggregate {
            public function getIterator(): \Traversable
            {
                return new \ArrayIterator([
                    'data-controller' => new SeparatedTokenList(['dropdown', 'tooltip']),
                    'data-action' => new SeparatedTokenList(['click->dropdown#toggle', 'mouseover->tooltip#show']),
                ]);
            }
        };

        $result = HtmlExtension::htmlAttr(new Environment(new ArrayLoader()), $object);

        self::assertSame('data-controller="dropdown tooltip" data-action="click-&gt;dropdown#toggle mouseover-&gt;tooltip#show"', $result);
    }

    public function testDataAttributeWithNonJsonEncodableValueThrowsRuntimeError()
    {
        $this->expectException(RuntimeError::class);
        $this->expectExceptionMessage('The "data-bad" attribute value cannot be JSON encoded.');

        HtmlExtension::htmlAttr(
            new Environment(new ArrayLoader()),
            ['data-bad' => [\INF]]  // INF cannot be JSON-encoded
        );
    }

    public function testNonStringableObjectAsAttributeValueThrowsRuntimeError()
    {
        $this->expectException(RuntimeError::class);
        $this->expectExceptionMessage('The "title" attribute value should be a scalar, an iterable, or an object implementing "Stringable"');

        HtmlExtension::htmlAttr(
            new Environment(new ArrayLoader()),
            ['title' => new \stdClass()]
        );
    }
}

class StringableStub implements \Stringable
{
    public function __construct(private readonly string $value)
    {
    }

    public function __toString(): string
    {
        return $this->value;
    }
}

class AttributeValueStub implements AttributeValueInterface
{
    public function __construct(private readonly ?string $value)
    {
    }

    public function getValue(): ?string
    {
        return $this->value;
    }
}
