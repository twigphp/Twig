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
 * Represents a call node.
 *
 * @package    twig
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
class Twig_Node_Call extends Twig_Node
{
  protected $name;
  protected $arguments;

  public function __construct($name, Twig_NodeList $arguments, $lineno, $tag = null)
  {
    parent::__construct($lineno, $tag);
    $this->name = $name;
    $this->arguments  = $arguments;
  }

  public function __toString()
  {
    return get_class($this).'('.$this->name.')';
  }

  public function compile($compiler)
  {
//    $compiler->subcompile($this->body);
  }

  public function getName()
  {
    return $this->name;
  }
}
