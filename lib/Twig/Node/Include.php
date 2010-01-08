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
class Twig_Node_Include extends Twig_Node implements Twig_NodeListInterface
{
  protected $expr;
  protected $sandboxed;
  protected $variables;

  public function __construct(Twig_Node_Expression $expr, $sandboxed, $variables, $lineno, $tag = null)
  {
    parent::__construct($lineno, $tag);

    $this->expr = $expr;
    $this->sandboxed = $sandboxed;
    $this->variables = $variables;
  }

  public function __toString()
  {
    return get_class($this).'('.$this->expr.')';
  }

  public function getNodes()
  {
    if (null === $this->variables)
    {
      return array(new Twig_Node_Text('', -1));
    }
    else
    {
      return array($this->variables);
    }

    return $this->variables->getNodes();
  }

  public function setNodes(array $nodes)
  {
    if (isset($nodes[0]) && -1 === $nodes[0]->getLine())
    {
      $this->variables = null;
    }
    else
    {
      $this->variables = $nodes[0];
    }
  }

  public function getIncludedFile()
  {
    return $this->expr;
  }

  public function isSandboxed()
  {
    return $this->sandboxed;
  }

  public function getVariables()
  {
    return $this->variables;
  }

  public function compile($compiler)
  {
    if (!$compiler->getEnvironment()->hasExtension('sandbox') && $this->sandboxed)
    {
      throw new Twig_SyntaxError('Unable to use the sanboxed attribute on an include if the sandbox extension is not enabled.', $this->lineno);
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
      ->raw(')->display(')
    ;

    if (null === $this->variables)
    {
      $compiler->raw('$context');
    }
    else
    {
      $compiler->subcompile($this->variables);
    }

    $compiler->raw(");\n");

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
