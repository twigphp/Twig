<?php

/*
 * This file is part of Twig.
 *
 * (c) 2009 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Interface all loaders must implement.
 *
 * @package    twig
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
interface Twig_LoaderInterface
{
  /**
   * Loads a template by name.
   *
   * @param  string $name The template name
   *
   * @return string The class name of the compiled template
   */
  public function load($name);
}
