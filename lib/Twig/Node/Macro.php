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
  protected $arguments;

  public function __construct($name, Twig_NodeList $body, $arguments, $lineno, $parent = null, $tag = null)
  {
    parent::__construct($lineno, $tag);
    $this->name = $name;
    $this->body = $body;
    $this->arguments = $arguments;
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
    $arguments = array();
    foreach ($this->arguments as $argument)
    {
      $arguments[] = '$'.$argument->getName().' = null';
    }

    $compiler
      ->addDebugInfo($this)
      ->write(sprintf("public function get%s(%s)\n", $this->name, implode(', ', $arguments)), "{\n")
      ->indent()
      ->write("\$context = array(\n")
      ->indent()
    ;

    foreach ($this->arguments as $argument)
    {
      $compiler
        ->write('')
        ->string($argument->getName())
        ->raw(' => $'.$argument->getName())
        ->raw(",\n")
      ;
    }

    $compiler
      ->outdent()
      ->write(");\n\n")
      ->subcompile($this->body)
      ->outdent()
      ->write("}\n\n")
    ;
  }
}
