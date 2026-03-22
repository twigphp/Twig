<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Extra\Html;

use Twig\Error\RuntimeError;
use Twig\Extra\Html\HtmlAttr\AttributeValueInterface;
use Twig\Extra\Html\HtmlAttr\InlineStyle;
use Twig\Extra\Html\HtmlAttr\SeparatedTokenList;
use Twig\Runtime\EscaperRuntime;

/**
 * Represents a set of HTML attributes that can be rendered as a string
 * or iterated over for spreading onto Twig Components.
 *
 * Usage:
 *   {# On a native HTML element (renders as string) #}
 *   <button {{ html_attr(merged) }}>Click</button>
 *
 *   {# On a Twig Component (spread as key-value pairs) #}
 *   <twig:Button {{ ...html_attr(merged) }}>Click</twig:Button>
 *
 * @implements \IteratorAggregate<string, string>
 */
final class HtmlAttributes implements \Stringable, \IteratorAggregate, \Countable
{
    /**
     * @param array<string, mixed> $attributes The raw merged attributes
     */
    public function __construct(
        private readonly array $attributes,
        private readonly EscaperRuntime $escaper,
    ) {
    }

    public function __toString(): string
    {
        $result = '';

        foreach ($this->resolveAttributes() as $name => $value) {
            $result .= $this->escaper->escape($name, 'html_attr_relaxed').'="'.$this->escaper->escape($value).'" ';
        }

        return trim($result);
    }

    /**
     * @return \Traversable<string, string>
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->resolveAttributes());
    }

    public function count(): int
    {
        return \count($this->resolveAttributes());
    }

    /**
     * Resolves the raw attributes into their final scalar values.
     *
     * This applies the same transformation logic as the original htmlAttr():
     * - aria-*: booleans converted to "true"/"false" strings
     * - data-*: non-scalar values JSON-encoded, true converted to "true"
     * - Iterables converted to SeparatedTokenList or InlineStyle
     * - AttributeValueInterface resolved via getValue()
     * - true becomes empty string
     * - null/false causes the attribute to be omitted
     *
     * @return array<string, string> The resolved attributes with scalar string values
     */
    private function resolveAttributes(): array
    {
        $resolved = [];

        foreach ($this->attributes as $name => $value) {
            if (str_starts_with($name, 'aria-')) {
                if (true === $value) {
                    $value = 'true';
                } elseif (false === $value) {
                    $value = 'false';
                }
            }

            if (str_starts_with($name, 'data-')) {
                if (!$value instanceof AttributeValueInterface && null !== $value && !\is_scalar($value)) {
                    try {
                        $value = json_encode($value, \JSON_THROW_ON_ERROR);
                    } catch (\JsonException $e) {
                        throw new RuntimeError(\sprintf('The "%s" attribute value cannot be JSON encoded.', $name), previous: $e);
                    }
                } elseif (true === $value) {
                    $value = 'true';
                }
            }

            if (!$value instanceof AttributeValueInterface && is_iterable($value)) {
                if ('style' === $name) {
                    $value = new InlineStyle($value);
                } else {
                    $value = new SeparatedTokenList($value);
                }
            }

            if ($value instanceof AttributeValueInterface) {
                $value = $value->getValue();
            }

            if (null === $value || false === $value) {
                continue;
            }

            if (true === $value) {
                $resolved[$name] = '';
                continue;
            }

            if (\is_object($value) && !$value instanceof \Stringable) {
                throw new RuntimeError(\sprintf('The "%s" attribute value should be a scalar, an iterable, or an object implementing "%s", got "%s".', $name, \Stringable::class, get_debug_type($value)));
            }

            $resolved[$name] = (string) $value;
        }

        return $resolved;
    }
}
