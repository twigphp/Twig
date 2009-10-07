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
 * Represents an include node.
 *
 * @package    twig
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
class Twig_Node_Include extends Twig_Node
{
  protected $expr;
  protected $sandboxed;

  public function __construct(Twig_Node_Expression $expr, $sandboxed, $lineno, $tag = null)
  {
    parent::__construct($lineno, $tag);

    $this->expr = $expr;
    $this->sandboxed = $sandboxed;
  }

  public function __toString()
  {
    return get_class($this).'('.$this->expr.')';
  }

  public function compile($compiler)
  {
    if (!$compiler->getEnvironment()->hasExtension('sandbox') && $this->sandboxed)
    {
      throw new Twig_SyntaxError('Unable to use the sanboxed attribute on an include if the sandbox extension is not enabled.');
    }

    $compiler->addDebugInfo($this);

    if ($this->sandboxed)
    {
      $compiler
        ->write("\$sandbox = \$this->env->getExtension('sandbox');\n")
        ->write("\$alreadySandboxed = \$sandbox->isSandboxed();\n")
        ->write("\$sandbox->enableSandbox();\n")
      ;
    }

    $compiler
      ->write('$this->env->loadTemplate(')
      ->subcompile($this->expr)
      ->raw(')->display($context);'."\n")
    ;

    if ($this->sandboxed)
    {
      $compiler
        ->write("if (!\$alreadySandboxed)\n", "{\n")
        ->indent()
        ->write("\$sandbox->disableSandbox();\n")
        ->outdent()
        ->write("}\n")
      ;
    }
  }
}
