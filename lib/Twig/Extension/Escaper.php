<?php

/*
 * This file is part of Twig.
 *
 * (c) 2009 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Twig_Extension_Escaper extends Twig_Extension
{
  protected $autoescape;

  public function __construct($autoescape = true)
  {
    $this->autoescape = $autoescape;
  }

  /**
   * Initializes the runtime environment.
   *
   * This is where you can load some file that contains filter functions for instance.
   */
  public function initRuntime()
  {
    require_once dirname(__FILE__).'/../runtime_escaper.php';
  }

  /**
   * Returns the token parser instance to add to the existing list.
   *
   * @return array An array of Twig_TokenParser instances
   */
  public function getTokenParsers()
  {
    return array(new Twig_TokenParser_AutoEscape());
  }

  /**
   * Returns the node transformer instances to add to the existing list.
   *
   * @return array An array of Twig_NodeTransformer instances
   */
  public function getNodeTransformers()
  {
    return array(new Twig_NodeTransformer_Escaper());
  }

  /**
   * Returns a list of filters to add to the existing list.
   *
   * @return array An array of filters
   */
  public function getFilters()
  {
    return array(
      'safe' => array('twig_safe_filter', false),
    );
  }

  public function isGlobal()
  {
    return $this->autoescape;
  }

  /**
   * Returns the name of the extension.
   *
   * @return string The extension name
   */
  public function getName()
  {
    return 'escaper';
  }
}
