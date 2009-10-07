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
class Twig_Node_Expression_Conditional extends Twig_Node_Expression
{
  protected $expr1;
  protected $expr2;
  protected $expr3;

  public function __construct(Twig_Node_Expression $expr1, Twig_Node_Expression $expr2, Twig_Node_Expression $expr3, $lineno)
  {
    parent::__construct($lineno);
    $this->expr1 = $expr1;
    $this->expr2 = $expr2;
    $this->expr3 = $expr3;
  }

  public function compile($compiler)
  {
    $compiler
      ->raw('(')
      ->subcompile($this->expr1)
      ->raw(') ? (')
      ->subcompile($this->expr2)
      ->raw(') : (')
      ->subcompile($this->expr3)
      ->raw(')')
    ;
  }
}
