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
 * Compiles a node to PHP code.
 *
 * @package    twig
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
class Twig_Compiler implements Twig_CompilerInterface
{
  protected $lastLine;
  protected $source;
  protected $indentation;
  protected $env;

  /**
   * Constructor.
   *
   * @param Twig_Environment $env The twig environment instance
   */
  public function __construct(Twig_Environment $env = null)
  {
    $this->env = $env;
  }

  public function setEnvironment(Twig_Environment $env)
  {
    $this->env = $env;
  }

  /**
   * Gets the current PHP code after compilation.
   *
   * @return string The PHP code
   */
  public function getSource()
  {
    return $this->source;
  }

  /**
   * Compiles a node.
   *
   * @param  Twig_Node $node The node to compile
   *
   * @return Twig_Compiler The current compiler instance
   */
  public function compile(Twig_Node $node)
  {
    $this->lastLine = null;
    $this->source = '';
    $this->indentation = 0;

    $node->compile($this);

    return $this;
  }

  public function subcompile(Twig_Node $node)
  {
    $node->compile($this);

    return $this;
  }

  /**
   * Adds a raw string to the compiled code.
   *
   * @param  string $string The string
   *
   * @return Twig_Compiler The current compiler instance
   */
  public function raw($string)
  {
    $this->source .= $string;

    return $this;
  }

  /**
   * Writes a string to the compiled code by adding indentation.
   *
   * @return Twig_Compiler The current compiler instance
   */
  public function write()
  {
    $strings = func_get_args();
    foreach ($strings as $string)
    {
      $this->source .= str_repeat(' ', $this->indentation * 2).$string;
    }

    return $this;
  }

  /**
   * Adds a quoted string to the compiled code.
   *
   * @param  string $string The string
   *
   * @return Twig_Compiler The current compiler instance
   */
  public function string($value)
  {
    $this->source .= sprintf('"%s"', str_replace('\\\n', "\n", addcslashes($value, "\t\"\$\\")));

    return $this;
  }

  /**
   * Returns a PHP representation of a given value.
   *
   * @param  mixed $value The value to convert
   *
   * @return Twig_Compiler The current compiler instance
   */
  public function repr($value)
  {
    if (is_int($value) || is_float($value))
    {
      $this->raw($value);
    }
    else if (is_null($value))
    {
      $this->raw('null');
    }
    else if (is_bool($value))
    {
      $this->raw($value ? 'true' : 'false');
    }
    else if (is_array($value))
    {
      $this->raw('array(');
      $i = 0;
      foreach ($value as $key => $value)
      {
        if ($i++)
        {
          $this->raw(', ');
        }
        $this->repr($key);
        $this->raw(' => ');
        $this->repr($value);
      }
      $this->raw(')');
    }
    else
    {
      $this->string($value);
    }

    return $this;
  }

  /**
   * Pushes the current context on the stack.
   *
   * @return Twig_Compiler The current compiler instance
   */
  public function pushContext()
  {
    // the (array) cast bypasses a PHP 5.2.6 bug
    $this->write('$context[\'_parent\'] = (array) $context;'."\n");

    return $this;
  }

  /**
   * Pops a context from the stack.
   *
   * @return Twig_Compiler The current compiler instance
   */
  public function popContext()
  {
    $this->write('$context = $context[\'_parent\'];'."\n");

    return $this;
  }

  /**
   * Adds debugging information.
   *
   * @param Twig_Node $node The related twig node
   *
   * @return Twig_Compiler The current compiler instance
   */
  public function addDebugInfo(Twig_Node $node)
  {
    if ($node->getLine() != $this->lastLine)
    {
      $this->lastLine = $node->getLine();
      $this->write("// line {$node->getLine()}\n");
    }

    return $this;
  }

  /**
   * Indents the generated code.
   *
   * @param integer $indent The number of indentation to add
   *
   * @return Twig_Compiler The current compiler instance
   */
  public function indent($step = 1)
  {
    $this->indentation += $step;

    return $this;
  }

  /**
   * Outdents the generated code.
   *
   * @param integer $indent The number of indentation to remove
   *
   * @return Twig_Compiler The current compiler instance
   */
  public function outdent($step = 1)
  {
    $this->indentation -= $step;

    return $this;
  }

  /**
   * Returns the environment instance related to this compiler.
   *
   * @return Twig_Environment The environment instance
   */
  public function getEnvironment()
  {
    return $this->env;
  }

  public function getTemplateClass($name)
  {
    return $this->getEnvironment()->getTemplateClass($name);
  }
}
