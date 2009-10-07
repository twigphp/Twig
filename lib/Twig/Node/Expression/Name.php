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
class Twig_Node_Expression_Name extends Twig_Node_Expression
{
  protected $name;

  public function __construct($name, $lineno)
  {
    parent::__construct($lineno);
    $this->name = $name;
  }

  public function __toString()
  {
    return get_class($this).'('.$this->name.')';
  }

  public function compile($compiler)
  {
    $compiler->raw(sprintf('(isset($context[\'%s\']) ? $context[\'%s\'] : null)', $this->name, $this->name));
  }

  public function getName()
  {
    return $this->name;
  }
}
