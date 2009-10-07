<?php

/*
 * This file is part of Twig.
 *
 * (c) 2009 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
abstract class Twig_NodeTransformer
{
  protected $env;

  public function setEnvironment(Twig_Environment $env)
  {
    $this->env = $env;
  }

  abstract public function visit(Twig_Node $node);

  protected function visitDeep(Twig_Node $node)
  {
    if (!$node instanceof Twig_NodeListInterface)
    {
      return $node;
    }

    $newNodes = array();
    foreach ($nodes = $node->getNodes() as $k => $n)
    {
      if (null !== $n = $this->visit($n))
      {
        $newNodes[$k] = $n;
      }
    }

    $node->setNodes($newNodes);

    return $node;
  }
}
