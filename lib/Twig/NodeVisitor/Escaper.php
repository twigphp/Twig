<?php

/*
 * This file is part of Twig.
 *
 * (c) 2009 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Twig_NodeVisitor_Escaper implements Twig_NodeVisitorInterface
{
  protected $statusStack = array();
  protected $blocks = array();

  public function enterNode(Twig_Node $node, Twig_Environment $env)
  {
    if ($node instanceof Twig_Node_AutoEscape)
    {
      $this->statusStack[] = $node->getValue();
    }
    elseif ($node instanceof Twig_Node_Print && true === $this->needEscaping($env))
    {
      return $this->escapeNode($node, $env);
    }
    elseif ($node instanceof Twig_Node_Block)
    {
      $this->statusStack[] = isset($this->blocks[$node->getName()]) ? $this->blocks[$node->getName()] : $this->needEscaping($env);
    }

    return $node;
  }

  public function leaveNode(Twig_Node $node, Twig_Environment $env)
  {
    if ($node instanceof Twig_Node_AutoEscape || $node instanceof Twig_Node_Block)
    {
      array_pop($this->statusStack);
    }
    elseif ($node instanceof Twig_Node_BlockReference)
    {
      $this->blocks[$node->getName()] = $this->needEscaping($env);
    }

    return $node;
  }

  protected function escapeNode(Twig_Node $node, Twig_Environment $env)
  {
    $expression = $node instanceof Twig_Node_Print ? $node->getExpression() : $node;

    if ($expression instanceof Twig_Node_Expression_Filter)
    {
      // don't escape if the primary node of the filter is not a variable
      $nodes = $expression->getNodes();
      if (!$nodes[0] instanceof Twig_Node_Expression_Name)
      {
        return $node;
      }

      // don't escape if there is already an "escaper" in the filter chain
      $filterMap = $env->getFilters();
      foreach ($expression->getFilters() as $filter)
      {
        if (isset($filterMap[$filter[0]]) && $filterMap[$filter[0]]->isEscaper())
        {
          return $node;
        }
      }
    }
    elseif (!$expression instanceof Twig_Node_Expression_GetAttr && !$expression instanceof Twig_Node_Expression_Name)
    {
      // don't escape if the node is not a variable
      return $node;
    }

    // escape
    if ($expression instanceof Twig_Node_Expression_Filter)
    {
      // escape all variables in filters arguments
      $filters = $expression->getFilters();
      foreach ($filters as $i => $filter)
      {
        foreach ($filter[1] as $j => $argument)
        {
          $filters[$i][1][$j] = $this->escapeNode($argument, $env);
        }
      }

      $expression->setFilters($filters);
      $expression->prependFilter($this->getEscaperFilter());

      return $node;
    }
    elseif ($node instanceof Twig_Node_Print)
    {
      return new Twig_Node_Print(
        new Twig_Node_Expression_Filter($expression, array($this->getEscaperFilter()), $node->getLine())
        , $node->getLine()
      );
    }
    else
    {
      return new Twig_Node_Expression_Filter($node, array($this->getEscaperFilter()), $node->getLine());
    }
  }

  protected function needEscaping(Twig_Environment $env)
  {
    if (count($this->statusStack))
    {
      return $this->statusStack[count($this->statusStack) - 1];
    }
    else
    {
      return $env->hasExtension('escaper') ? $env->getExtension('escaper')->isGlobal() : false;
    }
  }

  protected function getEscaperFilter()
  {
    return array('escape', array());
  }
}
