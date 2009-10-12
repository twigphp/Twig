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
class Twig_Node_Expression_GetAttr extends Twig_Node_Expression implements Twig_NodeListInterface
{
  protected $node;
  protected $attr;
  protected $arguments;

  public function __construct(Twig_Node $node, $attr, $arguments, $lineno, $token_value)
  {
    parent::__construct($lineno);
    $this->node = $node;
    $this->attr = $attr;
    $this->arguments = $arguments;
    $this->token_value = $token_value;
  }

  public function __toString()
  {
    return get_class($this).'('.$this->node.', '.$this->attr.')';
  }

  public function getNodes()
  {
    return array($this->node);
  }

  public function setNodes(array $nodes)
  {
    $this->node = $nodes[0];
  }

  public function compile($compiler)
  {
    $compiler
      ->raw('$this->getAttribute(')
      ->subcompile($this->node)
      ->raw(', ')
      ->subcompile($this->attr)
      ->raw(', array(')
    ;

    foreach ($this->arguments as $node)
    {
      $compiler
        ->subcompile($node)
        ->raw(', ')
      ;
    }

    $compiler->raw(')');

    if ('[' == $this->token_value) // Don't look for functions if they're using foo[bar]
    {
      $compiler->raw(', true');
    }

    $compiler->raw(')');
  }
}
