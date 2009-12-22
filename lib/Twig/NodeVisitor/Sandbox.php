<?php

/*
 * This file is part of Twig.
 *
 * (c) 2009 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Twig_NodeVisitor_Sandbox implements Twig_NodeVisitorInterface
{
  protected $inAModule = false;
  protected $tags;
  protected $filters;

  public function enterNode(Twig_Node $node, Twig_Environment $env)
  {
    if ($node instanceof Twig_Node_Module)
    {
      $this->inAModule = true;
      $this->tags = array();
      $this->filters = array();

      return $node;
    }
    elseif ($this->inAModule)
    {
      // look for tags
      if ($node->getTag())
      {
        $this->tags[$node->getTag()] = true;
      }

      // look for filters
      if ($node instanceof Twig_Node_Expression_Filter)
      {
        foreach ($node->getFilters() as $filter)
        {
          $this->filters[$filter[0]] = true;
        }
      }
    }

    return $node;
  }

  public function leaveNode(Twig_Node $node, Twig_Environment $env)
  {
    if ($node instanceof Twig_Node_Module)
    {
      $node->setUsedFilters(array_keys($this->filters));
      $node->setUsedTags(array_keys($this->tags));
      $this->inAModule = false;
    }

    return $node;
  }
}
