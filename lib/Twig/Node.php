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
 * Represents a node in the AST.
 *
 * @package    twig
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
abstract class Twig_Node
{
  protected $lineno;
  protected $tag;

  public function __construct($lineno, $tag = null)
  {
    $this->lineno = $lineno;
    $this->tag = $tag;
  }

  public function __toString()
  {
    return get_class($this).'()';
  }

  abstract public function compile($compiler);

  public function getLine()
  {
    return $this->lineno;
  }

  public function getTag()
  {
    return $this->tag;
  }
}
