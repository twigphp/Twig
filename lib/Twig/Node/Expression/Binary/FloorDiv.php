<?php

/*
 * This file is part of Twig.
 *
 * (c) 2009 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Twig_Node_Expression_Binary_FloorDiv extends Twig_Node_Expression_Binary
{
  public function compile($compiler)
  {
    $compiler
      ->raw('floor(')
      ->subcompile($this->left)
      ->raw(' / ')
      ->subcompile($this->right)
      ->raw(')')
    ;
  }

  public function operator($compiler)
  {
    return;
  }
}
