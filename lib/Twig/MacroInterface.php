<?php

/*
 * This file is part of Twig.
 *
 * (c) 2009 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
interface Twig_MacroInterface
{
  /**
   * Returns the bound environment for this template.
   *
   * @return Twig_Environment The current environment
   */
  public function getEnvironment();
}
