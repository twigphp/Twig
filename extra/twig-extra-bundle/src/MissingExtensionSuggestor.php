<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Extra\TwigExtraBundle;

use Twig\Error\SyntaxError;

final class MissingExtensionSuggestor
{
    public function suggestFilter(string $name): bool
    {
        if ($filter = Extensions::getFilter($name)) {
            throw new SyntaxError(sprintf('The "%s" filter is part of the %s, which is not installed/enabled; try running "composer require %s".', $name, $filter[0], $filter[1]));
        }

        return false;
    }

    public function suggestFunction(string $name): bool
    {
        if ($function = Extensions::getFunction($name)) {
            throw new SyntaxError(sprintf('The "%s" function is part of the %s, which is not installed/enabled; try running "composer require %s".', $name, $function[0], $function[1]));
        }

        return false;
    }

    public function suggestTag(string $name): bool
    {
        if ($function = Extensions::getTag($name)) {
            throw new SyntaxError(sprintf('The "%s" tag is part of the %s, which is not installed/enabled; try running "composer require %s".', $name, $function[0], $function[1]));
        }

        return false;
    }
}
