<?php

class Twig_Node_Set extends Twig_Node
{
  protected $names;
  protected $value;
  protected $isMultitarget;

  public function __construct($isMultitarget, $names, Twig_Node_Expression $value, $lineno, $tag = null)
  {
    parent::__construct($lineno, $tag);

    $this->isMultitarget = $isMultitarget;
    $this->names = $names;
    $this->value = $value;
  }

  public function compile($compiler)
  {
    $compiler->addDebugInfo($this);

    if ($this->isMultitarget)
    {
      $compiler->write('list(');
      foreach ($this->names as $idx => $node)
      {
        if ($idx)
        {
          $compiler->raw(', ');
        }

        $compiler
          ->raw('$context[')
          ->string($node->getName())
          ->raw(']')
        ;
      }
      $compiler->raw(')');
    }
    else
    {
      $compiler
        ->write('$context[')
        ->string($this->names->getName())
        ->raw(']')
      ;
    }

    $compiler
      ->raw(' = ')
      ->subcompile($this->value)
      ->raw(";\n")
    ;
  }
}
