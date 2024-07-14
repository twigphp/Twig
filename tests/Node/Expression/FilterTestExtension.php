<?php

namespace Twig\Tests\Node\Expression;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class FilterTestExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('first_class_callable_static', self::staticMethod(...)),
            new TwigFilter('first_class_callable_object', $this->objectMethod(...)),
        ];
    }

    public static function staticMethod()
    {
    }

    public function objectMethod()
    {
    }
}
