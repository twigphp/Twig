<?php

namespace Twig\Extension;

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Twig\TwigFunction;

class EnumExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('enum_cases', __CLASS__.'::enumCases'),
        ];
    }

    /**
     * @template T of \BackedEnum
     *
     * @param class-string<T> $backedEnum
     *
     * @return T[]
     */
    public static function enumCases(string $backedEnum): array
    {
        if (!is_a($backedEnum, \BackedEnum::class, true)) {
            throw new \InvalidArgumentException(sprintf('The enum must be a "\BackedEnum", "%s" given.', $backedEnum));
        }

        return $backedEnum::cases();
    }
}
