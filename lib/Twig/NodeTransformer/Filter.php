<?php

/*
 * This file is part of Twig.
 *
 * (c) 2009 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Twig_NodeTransformer_Filter extends Twig_NodeTransformer
{
  protected $statusStack = array();

  public function visit(Twig_Node $node)
  {
    // filter?
    if ($node instanceof Twig_Node_Filter)
    {
      $this->statusStack[] = $node->getFilters();

      $node = $this->visitDeep($node);

      array_pop($this->statusStack);

      return $node;
    }

    if (!$node instanceof Twig_Node_Print && !$node instanceof Twig_Node_Text)
    {
      return $this->visitDeep($node);
    }

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
