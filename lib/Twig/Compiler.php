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
 */
class Twig_Compiler implements Twig_CompilerInterface
{
    protected $env;

    protected $source;
    protected $indentation;
    protected $lastLine;

    /**
     * Constructor.
     *
     * @param Twig_Environment $env The twig environment instance
     */
    public function __construct(Twig_Environment $env)
    {
        $this->env = $env;
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

    /**
     * Compiles a node and returns the resulting code.
     *
     * @param Twig_NodeInterface $node   The node to compile
     * @param integer            $indent The current indentation
     *
     * @return string The compiled source
     */
    public function compile(Twig_NodeInterface $node, $indentation = 0)
    {
        $this->source      = '';
        $this->indentation = $indentation;
        $this->lastLine    = null;

        $node->compile($this);

        return $this->source;
    }

    /**
     * Compiles a node and appends the result to the compiled code.
     *
     * @param  Twig_NodeInterface $node The node to compile
     *
     * @return Twig_Compiler The current compiler instance
     */
    public function subcompile(Twig_NodeInterface $node)
    {
        $node->compile($this);

        return $this;
    }

    /**
     * Writes a string to the compiled code by adding
     * a newline and indentation before the string.
     *
     * @param  string $string The string
     *
     * @return Twig_Compiler The current compiler instance
     */
    public function write($string)
    {
        if ('' !== $this->source) {
            $this->source .= "\n";
        }

        $this->source .= str_repeat(' ', $this->indentation * 4) . $string;

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
     * Adds a quoted string to the compiled code.
     *
     * @param  string $string The string
     *
     * @return Twig_Compiler The current compiler instance
     */
    public function string($value)
    {
        $this->source .= '"' . addcslashes($value, "\t\"\$\\") . '"';

        return $this;
    }

    /**
     * Adds a PHP representation of a given value.
     *
     * @param  mixed $value The value to convert
     *
     * @return Twig_Compiler The current compiler instance
     */
    public function repr($value)
    {
        if (is_int($value) || is_float($value)) {
            $this->raw($value);
        } elseif (null === $value) {
            $this->raw('null');
        } elseif (is_bool($value)) {
            $this->raw($value ? 'true' : 'false');
        } elseif (is_array($value)) {
            $this->raw('array(');
            $i = 0;
            foreach ($value as $key => $value) {
                if ($i++) {
                    $this->raw(', ');
                }
                $this->repr($key);
                $this->raw(' => ');
                $this->repr($value);
            }
            $this->raw(')');
        } else {
            $this->string($value);
        }

        return $this;
    }

    /**
     * Adds debugging information.
     *
     * @param Twig_NodeInterface $node The related twig node
     *
     * @return Twig_Compiler The current compiler instance
     */
    public function addDebugInfo(Twig_NodeInterface $node)
    {
        if ($node->getLine() != $this->lastLine) {
            $this->lastLine = $node->getLine();
            $this->write('// line '.$node->getLine());
        }

        return $this;
    }

    /**
     * Indents the generated code.
     *
     * @return Twig_Compiler The current compiler instance
     */
    public function indent()
    {
        ++$this->indentation;

        return $this;
    }

    /**
     * Outdents the generated code.
     *
     * @return Twig_Compiler The current compiler instance
     */
    public function outdent()
    {
        --$this->indentation;

        if ($this->indentation < 0) {
            throw new Twig_Error('Unable to call outdent() as the indentation would become negative');
        }

        return $this;
    }
}
