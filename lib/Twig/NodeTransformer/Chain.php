<?php

/*
 * This file is part of Twig.
 *
 * (c) 2009 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Twig_NodeTransformer_Chain extends Twig_NodeTransformer
{
  protected $transformers;

  public function __construct(array $transformers)
  {
    $this->transformers = $transformers;
  }

  public function setEnvironment(Twig_Environment $env)
  {
    parent::setEnvironment($env);

    foreach ($this->transformers as $transformer)
    {
      $transformer->setEnvironment($env);
    }
  }

  public function visit(Twig_Node $node)
  {
    foreach ($this->transformers as $transformer)
    {
      $node = $transformer->visit($node);
    }

    return $node;
  }
}
