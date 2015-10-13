<?php

/*
 * This file is part of Twig.
 *
 * (c) 2010-2012 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Represents a template function.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @see http://twig.sensiolabs.org/doc/templates.html#functions
 */
class Twig_Function
{
    private $name;
    private $callable;
    private $options;
    private $arguments = array();

    /**
     * Creates a template function.
     *
     * @param string        $name     Name of this function
     * @param callable|null $callable A callable implementing the function. If null, you need to overwrite the "node_class" option to customize compilation.
     * @param array         $options  Options array
     */
    public function __construct($name, callable $callable = null, array $options = array())
    {
        $this->name = $name;
        $this->callable = $callable;
        $this->options = array_merge(array(
            'needs_environment' => false,
            'needs_context' => false,
            'is_variadic' => false,
            'is_safe' => null,
            'is_safe_callback' => null,
            'node_class' => 'Twig_Node_Expression_Function',
            'deprecated' => false,
            'alternative' => null,
        ), $options);
    }

    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns the callable to execute for this function.
     *
     * @return callable|null
     */
    public function getCallable()
    {
        return $this->callable;
    }

    public function getNodeClass()
    {
        return $this->options['node_class'];
    }

    public function setArguments($arguments)
    {
        $this->arguments = $arguments;
    }

    public function getArguments()
    {
        return $this->arguments;
    }

    public function needsEnvironment()
    {
        return $this->options['needs_environment'];
    }

    public function needsContext()
    {
        return $this->options['needs_context'];
    }

    public function getSafe(Twig_Node $functionArgs)
    {
        if (null !== $this->options['is_safe']) {
            return $this->options['is_safe'];
        }

        if (null !== $this->options['is_safe_callback']) {
            return $this->options['is_safe_callback']($functionArgs);
        }

        return array();
    }

    public function isVariadic()
    {
        return $this->options['is_variadic'];
    }

    public function isDeprecated()
    {
        return $this->options['deprecated'];
    }

    public function getAlternative()
    {
        return $this->options['alternative'];
    }
}
