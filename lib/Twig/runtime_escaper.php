<?php

/*
 * This file is part of Twig.
 *
 * (c) 2009 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// tells the escaper node transformer that the string is safe
function twig_safe_filter($string)
{
  return $string;
}
