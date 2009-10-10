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
 * Represents a module node.
 *
 * @package    twig
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
class Twig_Node_Module extends Twig_Node implements Twig_NodeListInterface
{
  protected $body;
  protected $extends;
  protected $blocks;
  protected $filename;
  protected $usedFilters;
  protected $usedTags;

  public function __construct(Twig_NodeList $body, $extends, $blocks, $filename)
  {
    parent::__construct(1);

    $this->body = $body;
    $this->extends = $extends;
    $this->blocks = array_values($blocks);
    $this->filename = $filename;
    $this->usedFilters = array();
    $this->usedTags = array();
  }

  public function __toString()
  {
    $repr = array(get_class($this).'(');
    foreach ($this->body->getNodes() as $node)
    {
      foreach (explode("\n", $node->__toString()) as $line)
      {
        $repr[] = '  '.$line;
      }
    }

    foreach ($this->blocks as $node)
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
    return array_merge(array($this->body), $this->blocks);
  }

  public function setNodes(array $nodes)
  {
    $this->body   = array_shift($nodes);
    $this->blocks = $nodes;
  }

  public function setUsedFilters(array $filters)
  {
    $this->usedFilters = $filters;
  }

  public function setUsedTags(array $tags)
  {
    $this->usedTags = $tags;
  }

  public function compile($compiler)
  {
    $sandboxed = $compiler->getEnvironment()->hasExtension('sandbox');

    $compiler->write("<?php\n\n");

    if (!is_null($this->extends))
    {
      $compiler
        ->write('$this->load(')
        ->repr($this->extends)
        ->raw(");\n\n")
      ;
    }

    $compiler
      ->write("/* $this->filename */\n")
      ->write('class __TwigTemplate_'.md5($this->filename))
    ;

    if (!is_null($this->extends))
    {
      $parent = md5($this->extends);
      $compiler
        ->raw(" extends __TwigTemplate_$parent\n")
        ->write("{\n")
        ->indent()
      ;
    }
    else
    {
      $compiler
        ->write(" extends ".$compiler->getEnvironment()->getBaseTemplateClass()."\n", "{\n")
        ->indent()
        ->write("public function display(array \$context)\n", "{\n")
        ->indent()
      ;

      if ($sandboxed)
      {
        $compiler->write("\$this->checkSecurity();\n");
      }

      $compiler
        ->write("\$this->env->initRuntime();\n\n")
        ->subcompile($this->body)
        ->outdent()
        ->write("}\n\n")
      ;
    }

    // blocks
    foreach ($this->blocks as $node)
    {
      $compiler->subcompile($node);
    }

    if ($sandboxed)
    {
      // sandbox information
      $compiler
        ->write("protected function checkSecurity()\n", "{\n")
        ->indent()
        ->write("\$this->env->getExtension('sandbox')->checkSecurity(\n")
        ->indent()
        ->write(!$this->usedTags ? "array(),\n" : "array('".implode('\', \'', $this->usedTags)."'),\n")
        ->write(!$this->usedFilters ? "array()\n" : "array('".implode('\', \'', $this->usedFilters)."')\n")
        ->outdent()
        ->write(");\n")
        ->outdent()
        ->write("}\n\n")
      ;
    }

    // debug information
    if ($compiler->getEnvironment()->isDebug())
    {
      $compiler
        ->write("public function __toString()\n", "{\n")
        ->indent()
        ->write('return ')
        ->string($this)
        ->raw(";\n")
        ->outdent()
        ->write("}\n\n")
      ;
    }

    $compiler
      ->outdent()
      ->write("}\n")
    ;
  }
}
