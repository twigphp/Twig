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
 * Loads a template from variables.
 *
 * @package    twig
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
class Twig_Loader_Var extends Twig_Loader
{
  protected $templates;
  protected $prefix;

  public function __construct(array $templates, $prefix)
  {
    $this->prefix = $prefix;
    $this->templates = array();
    foreach ($templates as $name => $template)
    {
      $this->templates[$this->prefix.'_'.$name] = $template;
    }
  }

  public function getSource($name)
  {
    if (!isset($this->templates[$this->prefix.'_'.$name]))
    {
      throw new LogicException(sprintf('Template "%s" is not defined.', $name));
    }

    return array(str_replace('%prefix%', $this->prefix.'_', $this->templates[$this->prefix.'_'.$name]), false);
  }
}
