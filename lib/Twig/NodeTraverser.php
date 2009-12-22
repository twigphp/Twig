<?php

/*
 * This file is part of Twig.
 *
 * (c) 2009 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Twig_NodeTraverser
{
  protected $env;

  public function __construct(Twig_Environment $env)
  {
    $this->env = $env;
  }

  public function traverse(Twig_Node $node, Twig_NodeVisitorInterface $visitor)
  {
    $node = $visitor->enterNode($node, $this->env);

    if ($node instanceof Twig_NodeListInterface)
    {
      $newNodes = array();
      foreach ($nodes = $node->getNodes() as $k => $n)
      {
        if (null !== $n = $this->traverse($n, $visitor))
        {
          $newNodes[$k] = $n;
        }
      }
      $node->setNodes($newNodes);
    }

    return $visitor->leaveNode($node, $this->env);
  }
}
