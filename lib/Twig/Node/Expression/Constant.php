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
class Twig_Node_Expression_Constant extends Twig_Node_Expression
{
  protected $value;

  public function __construct($value, $lineno)
  {
    parent::__construct($lineno);
    $this->value = $value;
  }

  public function __toString()
  {
    return get_class($this).'('.$this->value.')';
  }

  public function compile($compiler)
  {
    $compiler->repr($this->value);
  }

  public function getValue()
  {
    return $this->value;
  }
}
