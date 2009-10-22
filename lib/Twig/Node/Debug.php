<?php

class Twig_Node_Debug extends Twig_Node
{
  protected $expr;

  public function __construct(Twig_Node_Expression $expr = null, $lineno, $tag = null)
  {
    parent::__construct($lineno, $tag);

    $this->expr = $expr;
  }

  public function compile($compiler)
  {
    $compiler->addDebugInfo($this);

    $compiler
      ->write("if (\$this->env->isDebug())\n", "{\n")
      ->indent()
      ->write('var_export(')
    ;

    if (null === $this->expr)
    {
      $compiler->raw('$context');
    }
    else
    {
      $compiler->subcompile($this->expr);
    }

    $compiler
      ->raw(");\n")
      ->outdent()
      ->write("}\n")
    ;
  }
}
