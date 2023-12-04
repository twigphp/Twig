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
 * This describes a list of alternative types.
 *
 * @author Joshua Behrens <code@joshua-behrens.de>
 */
final class UnionType implements TypeInterface
{
    /**
     * @var list<TypeInterface>
     */
    private array $types;

    /**
     * @param list<TypeInterface> $types
     */
    public function __construct(array $types)
    {
        $items = [];

        foreach ($types as $type) {
            if ($type instanceof UnionType) {
                foreach ($type->getTypes() as $innerType) {
                    $items[] = $innerType;
                }
            } else {
                $items[] = $type;
            }
        }

        $this->types = $items;
    }

    /**
     * @return list<TypeInterface>
     */
    public function getTypes(): array
    {
        return $this->types;
    }

    public function getAttributeType(string|int $attribute): ?TypeInterface
    {
        $result = [];

        foreach ($this->types as $type) {
            $attributeType = $type->getAttributeType($attribute);

            if ($attributeType !== null) {
                $result[] = $attributeType;
            }
        }

        if ($result === []) {
            return null;
        }

        if (\count($result) === 1) {
            return $result[0];
        }

        return new UnionType($result);
    }
}
