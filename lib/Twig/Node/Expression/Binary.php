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
abstract class Twig_Node_Expression_Binary extends Twig_Node_Expression
{
  protected $left;
  protected $right;

  public function __construct(Twig_Node $left, Twig_Node $right, $lineno)
  {
    parent::__construct($lineno);
    $this->left = $left;
    $this->right = $right;
  }

  public function __toString()
  {
    $repr = array(get_class($this).'(');

    foreach (explode("\n", $this->left->__toString()) as $line)
    {
      $repr[] = '  '.$line;
    }

    $repr[] = ', ';

    foreach (explode("\n", $this->right->__toString()) as $line)
    {
      $repr[] = '  '.$line;
    }

    $repr[] = ')';

    return implode("\n", $repr);
  }

  public function compile($compiler)
  {
    $compiler
      ->raw('(')
      ->subcompile($this->left)
      ->raw(') ')
    ;
    $this->operator($compiler);
    $compiler
      ->raw(' (')
      ->subcompile($this->right)
      ->raw(')')
    ;
  }

  abstract public function operator($compiler);
}
