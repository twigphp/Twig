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
 * Describe any type, that is used as a typehint and access to its attribute types.
 *
 * @author Joshua Behrens <code@joshua-behrens.de>
 */
interface TypeInterface
{
    public function getAttributeType(string|int $attribute): ?TypeInterface;
}
