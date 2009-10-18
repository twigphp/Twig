<?php

/*
 * This file is part of Twig.
 *
 * (c) 2009 Fabien Potencier
 * (c) 2009 Armin Ronacher
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Twig_Node_Expression_Filter extends Twig_Node_Expression implements Twig_NodeListInterface
{
  protected $node;
  protected $filters;

  public function __construct(Twig_Node $node, array $filters, $lineno, $tag = null)
  {
    parent::__construct($lineno, $tag);

    $this->node = $node;
    $this->filters = $filters;
  }

  public function __toString()
  {
    $filters = array();
    foreach ($this->filters as $filter)
    {
      $filters[] = $filter[0].'('.implode(', ', $filter[1]).')';
    }

    $repr = array(get_class($this).'(');

    foreach (explode("\n", $this->node->__toString()) as $line)
    {
      $repr[] = '  '.$line;
    }

    $repr[] = '  ('.implode(', ', $filters).')';
    $repr[] = ')';

    return implode("\n", $repr);
  }

  public function getNodes()
  {
    return array($this->node);
  }

  public function setNodes(array $nodes)
  {
    $this->node = $nodes[0];
  }

  public function compile($compiler)
  {
    $filterMap = $compiler->getEnvironment()->getFilters();

    $postponed = array();
    for ($i = count($this->filters) - 1; $i >= 0; --$i)
    {
      list($name, $attrs) = $this->filters[$i];
      if (!isset($filterMap[$name]))
      {
        $compiler
          ->raw('$this->resolveMissingFilter(')
          ->repr($name)
          ->raw(', ')
        ;
      }
      else
      {
        $compiler->raw($filterMap[$name][0].($filterMap[$name][1] ? '($this->getEnvironment(), ' : '('));
      }
      $postponed[] = $attrs;
    }
    $this->node->compile($compiler);
    foreach (array_reverse($postponed) as $attributes)
    {
      foreach ($attributes as $node)
      {
        $compiler
          ->raw(', ')
          ->subcompile($node)
        ;
      }
      $compiler->raw(')');
    }
  }

  public function getFilters()
  {
    return $this->filters;
  }

  public function appendFilter($filter)
  {
    $this->filters[] = $filter;
  }

  public function appendFilters(array $filters)
  {
    $this->filters = array_merge($this->filters, $filters);
  }

  public function hasFilter($name)
  {
    foreach ($this->filters as $filter)
    {
      if ($name == $filter[0])
      {
        return true;
      }
    }

    return false;
  }
}
