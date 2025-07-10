<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Tests\Extension\Fixtures;

use Twig\Attribute\AsTwigFilter;

class FilterWithoutValue
{
    #[AsTwigFilter('my_filter')]
    public function myFilter()
    {
    }
}
