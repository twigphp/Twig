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
 * Represents a list of nodes.
 *
 * @package    twig
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
class Twig_NodeList extends Twig_Node implements Twig_NodeListInterface
{
  protected $nodes;

  public function __construct(array $nodes, $lineno = 0)
  {
    parent::__construct($lineno);

    $this->nodes = $nodes;
  }

  public function __toString()
  {
    $repr = array(get_class($this).'(');
    foreach ($this->nodes as $node)
    {
      foreach (explode("\n", $node->__toString()) as $line)
      {
        $repr[] = '  '.$line;
      }
    }

    return implode("\n", $repr);
  }

  public function compile($compiler)
  {
    foreach ($this->nodes as $node)
    {
      $node->compile($compiler);
    }
  }

  public function getNodes()
  {
    return $this->nodes;
  }

  public function setNodes(array $nodes)
  {
    $this->nodes = $nodes;
  }
}
