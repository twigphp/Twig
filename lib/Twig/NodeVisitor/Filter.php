<?php

/*
 * This file is part of Twig.
 *
 * (c) 2009 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Twig_NodeVisitor_Filter implements Twig_NodeVisitorInterface
{
  protected $statusStack = array();

  public function enterNode(Twig_Node $node, Twig_Environment $env)
  {
    if ($node instanceof Twig_Node_Filter)
    {
      $this->statusStack[] = $node->getFilters();
    }
    elseif ($node instanceof Twig_Node_Print || $node instanceof Twig_Node_Text)
    {
      return $this->applyFilters($node);
    }

    return $node;
  }

  public function leaveNode(Twig_Node $node, Twig_Environment $env)
  {
    if ($node instanceof Twig_Node_Filter)
    {
      array_pop($this->statusStack);
    }

    return $node;
  }

  protected function applyFilters(Twig_Node $node)
  {
    if (false === $filters = $this->getCurrentFilters())
    {
      return $node;
    }

    if ($node instanceof Twig_Node_Text)
    {
      $expression = new Twig_Node_Expression_Constant($node->getData(), $node->getLine());
    }
    else
    {
      $expression = $node->getExpression();
    }

    // filters
    if ($expression instanceof Twig_Node_Expression_Filter)
    {
      $expression->appendFilters($filters);

      return $node;
    }
    else
    {
      return new Twig_Node_Print(
        new Twig_Node_Expression_Filter($expression, $filters, $node->getLine())
        , $node->getLine()
      );
    }
  }

  protected function getCurrentFilters()
  {
    return count($this->statusStack) ? $this->statusStack[count($this->statusStack) - 1] : false;
  }
}
