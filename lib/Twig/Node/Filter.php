<?php

/*
 * This file is part of Twig.
 *
 * (c) 2009 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Represents a filter node.
 *
 * @package    twig
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
class Twig_Node_Filter extends Twig_Node implements Twig_NodeListInterface
{
  protected $filters;
  protected $body;

  public function __construct($filters, Twig_NodeList $body, $lineno, $tag = null)
  {
    parent::__construct($lineno, $tag);
    $this->filters = $filters;
    $this->body  = $body;
  }

  public function __toString()
  {
    return get_class($this).'('.$this->filters.')';
  }

  public function getNodes()
  {
    return $this->body->getNodes();
  }

  public function setNodes(array $nodes)
  {
    $this->body = new Twig_NodeList($nodes, $this->lineno);
  }

  public function compile($compiler)
  {
    $compiler->subcompile($this->body);
  }

  public function getFilters()
  {
    return $this->filters;
  }
}
