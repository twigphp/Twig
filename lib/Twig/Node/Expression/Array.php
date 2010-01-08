<?php

/*
 * This file is part of Twig.
 *
 * (c) 2009 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Twig_Node_Expression_Array extends Twig_Node_Expression implements Twig_NodeListInterface
{
  protected $elements;

  public function __construct($elements, $lineno)
  {
    parent::__construct($lineno);

    $this->elements = $elements;
  }

  public function __toString()
  {
    $repr = array(get_class($this).'(');
    foreach ($this->elements as $name => $node)
    {
      foreach (explode("\n", '  '.$name.' => '.$node) as $line)
      {
        $repr[] = '    '.$line;
      }
    }
    $repr[] = ')';

    return implode("\n", $repr);
  }

  public function getNodes()
  {
    return $this->elements;
  }

  public function setNodes(array $nodes)
  {
    $this->elements = $nodes;
  }

  public function compile($compiler)
  {
    $compiler->raw('array(');
    $first = true;
    foreach ($this->elements as $name => $node)
    {
      if (!$first)
      {
        $compiler->raw(', ');
      }
      $first = false;

      $compiler
        ->repr($name)
        ->raw(' => ')
        ->subcompile($node)
      ;
    }
    $compiler->raw(')');
  }

  public function getElements()
  {
    return $this->elements;
  }
}
