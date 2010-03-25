<?php

/*
 * This file is part of Twig.
 *
 * (c) 2010 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Represents a set node.
 *
 * @package    twig
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
class Twig_Node_Set extends Twig_Node implements Twig_NodeListInterface
{
  protected $names;
  protected $values;
  protected $isMultitarget;
  protected $capture;

  public function __construct($isMultitarget, $capture, $names, $values, $lineno, $tag = null)
  {
    parent::__construct($lineno, $tag);

    $this->isMultitarget = $isMultitarget;
    $this->capture = $capture;
    $this->names = $names;
    $this->values = $values;
  }

  public function __toString()
  {
    $repr = array(get_class($this).'('.($this->isMultitarget ? implode(', ', $this->names) : $this->names).',');
    foreach ($this->isMultitarget ? $this->values : array($this->values) as $node)
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
    if ($this->isMultitarget)
    {
      return $this->values;
    }
    else
    {
      return array($this->values);
    }
  }

  public function setNodes(array $nodes)
  {
    $this->values = $this->isMultitarget ? $nodes : $nodes[0];
  }

  public function compile($compiler)
  {
    $compiler->addDebugInfo($this);

    if ($this->isMultitarget)
    {
      $compiler->write('list(');
      foreach ($this->names as $idx => $node)
      {
        if ($idx)
        {
          $compiler->raw(', ');
        }

        $compiler
          ->raw('$context[')
          ->string($node->getName())
          ->raw(']')
        ;
      }
      $compiler->raw(')');
    }
    else
    {
      if ($this->capture)
      {
        $compiler
          ->write("ob_start();\n")
          ->subcompile($this->values)
        ;
      }

      $compiler
        ->write('$context[')
        ->string($this->names->getName())
        ->raw(']')
      ;

      if ($this->capture)
      {
        $compiler->raw(" = ob_get_clean()");
      }
    }

    if (!$this->capture)
    {
      $compiler->raw(' = ');

      if ($this->isMultitarget)
      {
        $compiler->write('array(');
        foreach ($this->values as $idx => $value)
        {
          if ($idx)
          {
            $compiler->raw(', ');
          }

          $compiler->subcompile($value);
        }
        $compiler->raw(')');
      }
      else
      {
        $compiler->subcompile($this->values);
      }
    }

    $compiler->raw(";\n");
  }

  public function getNames()
  {
    return $this->names;
  }
}
