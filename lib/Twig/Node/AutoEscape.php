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
 * Represents an autoescape node.
 *
 * The value is the escaping strategy (can be html, js, ...)
 *
 * The true value is equivalent to html.
 *
 * If autoescaping is disabled, then the value is false.
 *
 * @package    twig
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
class Twig_Node_AutoEscape extends Twig_Node implements Twig_NodeListInterface
{
  protected $value;
  protected $body;

  public function __construct($value, Twig_NodeList $body, $lineno, $tag = null)
  {
    parent::__construct($lineno, $tag);
    $this->value = $value;
    $this->body  = $body;
  }

  public function __toString()
  {
    $repr = array(get_class($this).'('.($this->value ? 'on' : 'off'));
    foreach (explode("\n", $this->body) as $line)
    {
      $repr[] = '    '.$line;
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

  public function compile($compiler)
  {
    $compiler->subcompile($this->body);
  }

  public function getValue()
  {
    return $this->value;
  }
}
