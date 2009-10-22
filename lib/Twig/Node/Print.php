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
 * Represents a node that outputs an expression.
 *
 * @package    twig
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
class Twig_Node_Print extends Twig_Node implements Twig_NodeListInterface
{
  protected $expr;

  public function __construct(Twig_Node_Expression $expr, $lineno, $tag = null)
  {
    parent::__construct($lineno, $tag);
    $this->expr = $expr;
  }

  public function __toString()
  {
    $repr = array(get_class($this).'(');
    foreach (explode("\n", $this->expr->__toString()) as $line)
    {
      $repr[] = '  '.$line;
    }
    $repr[] = ')';

    return implode("\n", $repr);
  }

  public function getNodes()
  {
    return array($this->expr);
  }

  public function setNodes(array $nodes)
  {
    $this->expr = $nodes[0];
  }

  public function compile($compiler)
  {
    $compiler
      ->addDebugInfo($this)
      ->write('echo ')
      ->subcompile($this->expr)
      ->raw(";\n")
    ;
  }

  public function getExpression()
  {
    return $this->expr;
  }
}
