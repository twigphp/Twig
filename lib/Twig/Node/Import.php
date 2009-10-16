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
 * Represents an import node.
 *
 * @package    twig
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
class Twig_Node_Import extends Twig_Node
{
  protected $macro;
  protected $var;

  public function __construct($macro, $var, $lineno, $tag = null)
  {
    parent::__construct($lineno, $tag);
    $this->macro = $macro;
    $this->var = $var;
  }

  public function __toString()
  {
    return get_class($this).'('.$this->macro.', '.$this->var.')';
  }

  public function compile($compiler)
  {
    $compiler
      ->addDebugInfo($this)
      ->write('$this->env->getLoader()->load(')
      ->string($this->macro)
      ->raw(");\n\n")
      ->write("if (!class_exists(")
      ->string('__TwigMacro_'.md5($this->macro))
      ->raw("))\n")
      ->write("{\n")
      ->indent()
      ->write(sprintf("throw new InvalidArgumentException('There is no defined macros in template \"%s\".');\n", $this->macro))
      ->outdent()
      ->write("}\n")
      ->write(sprintf("\$context["))
      ->string($this->var)
      ->raw(sprintf("] = new __TwigMacro_%s(\$this->env);\n", md5($this->macro)))
    ;
  }

  public function getMacro()
  {
    return $this->macro;
  }

  public function getVar()
  {
    return $this->var;
  }
}
