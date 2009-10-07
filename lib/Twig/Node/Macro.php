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
 * Represents a macro node.
 *
 * @package    twig
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
class Twig_Node_Macro extends Twig_Node implements Twig_NodeListInterface
{
  protected $name;
  protected $body;

  public function __construct($name, Twig_NodeList $body, $lineno, $tag = null)
  {
    parent::__construct($lineno, $tag);
    $this->name = $name;
    $this->body  = $body;
  }

  public function __toString()
  {
    return get_class($this).'('.$this->name.')';
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

  public function getName()
  {
    return $this->name;
  }
}
