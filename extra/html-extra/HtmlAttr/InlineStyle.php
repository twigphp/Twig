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
final class InlineStyle implements MergeableInterface, AttributeValueInterface
{
    private readonly array $value;

    public function __construct(mixed $value)
    {
        if (!is_iterable($value)) {
            throw new RuntimeError('InlineStyle can only be created from iterable values.');
        }

        $this->value = [...$value];
    }

    public function mergeInto(mixed $previous): mixed
    {
        if ($previous instanceof self) {
            return new self([...$previous->value, ...$this->value]);
        }

        if (is_iterable($previous)) {
            return new self([...$previous, ...$this->value]);
        }

        throw new RuntimeError('Attributes using InlineStyle can only be merged with iterables or other InlineStyle instances.');
    }

    public function appendFrom(mixed $newValue): mixed
    {
        if (!is_iterable($newValue)) {
            throw new RuntimeError('Only iterable values can be appended to InlineStyle.');
        }

        return new self([...$this->value, ...$newValue]);
    }

    public function getValue(): ?string
    {
        $style = '';
        foreach ($this->value as $name => $value) {
            if (empty($value) || true === $value) {
                continue;
            }
            if (is_numeric($name)) {
                $style .= trim($value, '; ').'; ';
            } else {
                $style .= $name.': '.$value.'; ';
            }
        }

        return trim($style) ?: null;
    }
}
