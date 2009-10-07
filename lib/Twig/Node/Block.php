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

/**
 * Represents a block node.
 *
 * @package    twig
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
class Twig_Node_Block extends Twig_Node implements Twig_NodeListInterface
{
  protected $name;
  protected $body;
  protected $parent;

  public function __construct($name, Twig_NodeList $body, $lineno, $parent = null, $tag = null)
  {
    parent::__construct($lineno, $tag);
    $this->name = $name;
    $this->body = $body;
    $this->parent = $parent;
  }

  public function __toString()
  {
    $repr = array(get_class($this).' '.$this->name.'(');
    foreach ($this->body->getNodes() as $node)
    {
      foreach (explode("\n", $node->__toString()) as $line)
      {
        $repr[] = '  '.$line;
      }
    }
    $repr[] = ')';

    return implode("\n", $repr);
  }

  public function getNodes()
  {
    return $this->body->getNodes();
  }

  public function setNodes(array $nodes)
  {
    $this->body = new Twig_NodeList($nodes, $this->lineno);
  }

  public function replace($other)
  {
    $this->body = $other->body;
  }

  public function compile($compiler)
  {
    $compiler
      ->addDebugInfo($this)
      ->write(sprintf("public function block_%s(\$context)\n", $this->name), "{\n")
      ->indent()
    ;

    $compiler
      ->subcompile($this->body)
      ->outdent()
      ->write("}\n\n")
    ;
  }

  public function getParent()
  {
    return $this->parent;
  }

  public function setParent($parent)
  {
    $this->parent = $parent;
  }
}
