<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Twig\TypeHint;

/**
 * Describes a type of an associative array.
 *
 * @author Joshua Behrens <code@joshua-behrens.de>
 */
class ArrayType extends Type
{
    /**
     * @var array<TypeInterface>
     */
    private array $attributes;

    /**
     * @param array<TypeInterface> $attributes
     */
    public function __construct(array $attributes)
    {
        parent::__construct('array');
        $this->attributes = $attributes;
    }

    /**
     * @return array<TypeInterface>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getAttributeType(string|int $attribute): ?TypeInterface
    {
        return $this->attributes[$attribute] ?? null;
    }
}
