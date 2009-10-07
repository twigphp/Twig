<?php

/*
 * This file is part of Twig.
 *
 * (c) 2009 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
abstract class Twig_Extension implements Twig_ExtensionInterface
{
  /**
   * Initializes the runtime environment.
   *
   * This is where you can load some file that contains filter functions for instance.
   */
  public function initRuntime()
  {
  }

  /**
   * Returns the token parser instances to add to the existing list.
   *
   * @return array An array of Twig_TokenParser instances
   */
  public function getTokenParsers()
  {
    return array();
  }

  /**
   * Returns the node transformer instances to add to the existing list.
   *
   * @return array An array of Twig_NodeTransformer instances
   */
  public function getNodeTransformers()
  {
    return array();
  }

  /**
   * Returns a list of filters to add to the existing list.
   *
   * @return array An array of filters
   */
  public function getFilters()
  {
    return array();
  }
}
