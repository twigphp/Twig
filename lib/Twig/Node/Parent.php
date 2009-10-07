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
 * Represents a parent node.
 *
 * @package    twig
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
class Twig_Node_Parent extends Twig_Node
{
  protected $blockName;

  public function __construct($blockName, $lineno, $tag = null)
  {
    parent::__construct($lineno, $tag);
    $this->blockName = $blockName;
  }

  public function __toString()
  {
    return get_class($this).'('.$this->blockName.')';
  }

  public function compile($compiler)
  {
    $compiler
      ->addDebugInfo($this)
      ->write('parent::block_'.$this->blockName.'($context);'."\n")
    ;
  }
}
