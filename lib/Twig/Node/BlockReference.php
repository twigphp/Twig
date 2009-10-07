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
 * Represents a block call node.
 *
 * @package    twig
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
class Twig_Node_BlockReference extends Twig_Node
{
  protected $name;

  public function __construct($name, $lineno, $tag = null)
  {
    parent::__construct($lineno, $tag);
    $this->name = $name;
  }

  public function __toString()
  {
    return get_class($this).'('.$this->name.')';
  }

  public function compile($compiler)
  {
    $compiler
      ->addDebugInfo($this)
      ->write(sprintf('$this->block_%s($context);'."\n", $this->name))
    ;
  }

  public function getName()
  {
    return $this->name;
  }
}
