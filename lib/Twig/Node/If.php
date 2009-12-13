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
 * Represents an if node.
 *
 * @package    twig
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
class Twig_Node_If extends Twig_Node implements Twig_NodeListInterface
{
  protected $tests;
  protected $else;

  public function __construct($tests, Twig_NodeList $else = null, $lineno, $tag = null)
  {
    parent::__construct($lineno, $tag);
    $this->tests = $tests;
    $this->else = $else;
  }

  public function __toString()
  {
    $repr = array(get_class($this).'(');
    foreach ($this->tests as $test)
    {
      foreach (explode("\n", $test[0].' => '.$test[1]) as $line)
      {
        $repr[] = '    '.$line;
      }
    }
    $repr[] = ')';

    if ($this->else)
    {
      foreach (explode("\n", $this->else) as $line)
      {
        $repr[] = '    '.$line;
      }
    }

    return implode("\n", $repr);
  }

  public function getNodes()
  {
    $nodes = array();
    foreach ($this->tests as $test)
    {
      $nodes[] = $test[1];
    }

    if ($this->else)
    {
      $nodes[] = $this->else;
    }

    return $nodes;
  }

  public function setNodes(array $nodes)
  {
    foreach ($this->tests as $i => $test)
    {
      $this->tests[$i][1] = $nodes[$i];
    }

    if ($this->else)
    {
      $nodes = $nodes[count($nodes) - 1];
    }
  }

  public function compile($compiler)
  {
    $compiler->addDebugInfo($this);
    $idx = 0;
    foreach ($this->tests as $test)
    {
      if ($idx++)
      {
        $compiler
          ->outdent()
          ->write("}\n", "elseif (")
        ;
      }
      else
      {
        $compiler
          ->write('if (')
        ;
      }

      $compiler
        ->subcompile($test[0])
        ->raw(")\n")
        ->write("{\n")
        ->indent()
        ->subcompile($test[1])
      ;
    }
    if (!is_null($this->else))
    {
      $compiler
        ->outdent()
        ->write("}\n", "else\n", "{\n")
        ->indent()
        ->subcompile($this->else)
      ;
    }

    $compiler
      ->outdent()
      ->write("}\n");
  }
}
