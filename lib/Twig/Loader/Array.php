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
 * Loads a template from an array.
 *
 * @package    twig
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
class Twig_Loader_Array extends Twig_Loader
{
  protected $templates;

  /**
   * Constructor.
   *
   * @param array   $templates  An array of templates (keys are the names, and values are the source code)
   * @param string  $cache      The compiler cache directory
   * @param Boolean $autoReload Whether to reload the template is the original source changed
   *
   * @see Twig_Loader
   */
  public function __construct(array $templates, $cache = null)
  {
    parent::__construct($cache);

    $this->templates = array();
    foreach ($templates as $name => $template)
    {
      $this->templates[$name] = $template;
    }
  }

  public function getSource($name)
  {
    if (!isset($this->templates[$name]))
    {
      throw new LogicException(sprintf('Template "%s" is not defined.', $name));
    }

    return array($this->templates[$name], false);
  }
}
