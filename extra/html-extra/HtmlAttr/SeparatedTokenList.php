<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Extra\Html\HtmlAttr;

use Twig\Error\RuntimeError;

/**
 * @author Matthias Pigulla <mp@webfactory.de>
 */
final class SeparatedTokenList implements AttributeValueInterface, MergeableInterface
{
    private readonly array $value;

    public function __construct(mixed $value, private readonly string $separator = ' ')
    {
        if (is_iterable($value)) {
            $this->value = [...$value];
        } elseif (\is_scalar($value)) {
            $this->value = [$value];
        } else {
            throw new RuntimeError('SeparatedTokenList can only be constructed from iterable or scalar values.');
        }
    }

    public function mergeInto(mixed $previous): mixed
    {
        if ($previous instanceof self && $previous->separator === $this->separator) {
            return new self([...$previous->value, ...$this->value], $this->separator);
        }

        if (is_iterable($previous)) {
            return new self([...$previous, ...$this->value], $this->separator);
        }

        throw new RuntimeError('SeparatedTokenList can only be merged with iterables or other SeparatedTokenList instances using the same separator.');
    }

    public function appendFrom(mixed $newValue): mixed
    {
        if (!is_iterable($newValue)) {
            throw new RuntimeError('Only iterable values can be appended to SeparatedTokenList.');
        }

        return new self([...$this->value, ...$newValue], $this->separator);
    }

    public function getValue(): ?string
    {
        $filtered = array_filter($this->value, static fn ($v) => null !== $v && false !== $v);

        // Omit attribute if list contains only false or null values
        if (!$filtered) {
            return null;
        }

        // true values are not printed, but result in the attribute not being omitted
        return trim(implode($this->separator, array_filter($filtered, static fn ($v) => true !== $v)));
    }
}
