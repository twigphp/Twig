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

use Twig\Attribute\AsTwigTest;

class TestWithoutValue
{
    #[AsTwigTest('my_test')]
    public function myTest()
    {
    }
}
