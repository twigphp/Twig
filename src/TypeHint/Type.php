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
 * Represents any type. Mainly used for scalar and non-object types.
 *
 * @author Joshua Behrens <code@joshua-behrens.de>
 */
class Type implements TypeInterface
{
    private string $type;

    public function __construct(string $type)
    {
        $this->type = $type;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getAttributeType(string|int $attribute): ?TypeInterface
    {
        return null;
    }
}
