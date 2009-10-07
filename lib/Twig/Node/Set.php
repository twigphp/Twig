<?php

class Twig_Node_Set extends Twig_Node
{
  protected $name;
  protected $value;

  public function __construct($name, Twig_Node_Expression $value, $lineno)
  {
    parent::__construct($lineno);

    $this->name = $name;
    $this->value = $value;
  }

  public function compile($compiler)
  {
    $compiler
      ->addDebugInfo($this)
      ->write('$context[')
      ->string($this->name)
      ->write('] = ')
      ->subcompile($this->value)
      ->raw(";\n")
    ;
  }
}
