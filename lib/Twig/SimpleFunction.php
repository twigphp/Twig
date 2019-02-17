<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Twig\TwigFunction;

/*
 * For Twig 1.x compatibility.
 */
class_exists('Twig_Function');

if (false) {
    final class Twig_SimpleFunction extends TwigFunction
    {
    }
}
