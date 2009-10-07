<?php

/*
 * This file is part of Twig.
 *
 * (c) 2009 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Twig_NodeTransformer_Escaper extends Twig_NodeTransformer
{
  protected $statusStack = array();

  public function visit(Twig_Node $node)
  {
    // autoescape?
    if ($node instanceof Twig_Node_AutoEscape)
    {
      $this->statusStack[] = $node->getValue();

      $node = $this->visitDeep($node);

      array_pop($this->statusStack);

      // remove the node
      return $node;
    }

    if (!$node instanceof Twig_Node_Print)
    {
      return $this->visitDeep($node);
    }

    if (false === $this->needEscaping())
    {
      return $node;
    }

    $expression = $node->getExpression();

    // don't escape if escape has already been called
    // or if we want the safe string
    if (
      $expression instanceof Twig_Node_Expression_Filter
      &&
      (
        $expression->hasFilter('escape')
        ||
        $expression->hasFilter('safe')
      )
    )
    {
      return $node;
    }

    // escape
    if ($expression instanceof Twig_Node_Expression_Filter)
    {
      $expression->appendFilter(array('escape', array()));

      return $node;
    }
    else
    {
      return new Twig_Node_Print(
        new Twig_Node_Expression_Filter($expression, array(array('escape', array())), $node->getLine())
        , $node->getLine()
      );
    }
  }

  protected function needEscaping()
  {
    if (count($this->statusStack))
    {
      return $this->statusStack[count($this->statusStack) - 1];
    }
    else
    {
      return $this->env->hasExtension('escaper') ? $this->env->getExtension('escaper')->isGlobal() : false;
    }
  }
}
