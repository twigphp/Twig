<?php

/*
 * This file is part of Twig.
 *
 * (c) 2009 Fabien Potencier
 * (c) 2009 Armin Ronacher
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Represents a text node.
 *
 * @package    twig
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
class Twig_Node_Text extends Twig_Node
{
  protected $data;

  public function __construct($data, $lineno)
  {
    parent::__construct($lineno);
    $this->data = $data;
  }

  public function __toString()
  {
    return get_class($this).'('.$this->data.')';
  }

  public function compile($compiler)
  {
    $compiler
      ->addDebugInfo($this)
      ->write('echo ')
      ->string($this->data)
      ->raw(";\n")
    ;
  }

  public function getData()
  {
    return $this->data;
  }
}
