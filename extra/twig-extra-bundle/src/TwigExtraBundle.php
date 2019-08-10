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

use Symfony\Component\HttpKernel\Bundle\Bundle;

class TwigExtraBundle extends Bundle
{
    public function getPath()
    {
        return \dirname(__DIR__);
    }
}
