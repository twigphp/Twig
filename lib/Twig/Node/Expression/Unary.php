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
abstract class Twig_Node_Expression_Unary extends Twig_Node_Expression
{
  protected $node;

  public function __construct(Twig_Node $node, $lineno)
  {
    parent::__construct($lineno);
    $this->node = $node;
  }

  public function __toString()
  {
    $repr = array(get_class($this).'(');

    foreach (explode("\n", $this->node->__toString()) as $line)
    {
      $repr[] = '  '.$line;
    }

    $repr[] = ')';

    return implode("\n", $repr);
  }
  public function compile($compiler)
  {
    $compiler->raw('(');
    $this->operator($compiler);
    $this->node->compile($compiler);
    $compiler->raw(')');
  }

  abstract public function operator($compiler);
}
