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
 * Represents a template test.
 *
 * @final
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @see http://twig.sensiolabs.org/doc/templates.html#test-operator
 */
class Twig_Test
{
    private $name;
    private $callable;
    private $options;

    /**
     * Creates a template test.
     *
     * @param string        $name     Name of this test
     * @param callable|null $callable A callable implementing the test. If null, you need to overwrite the "node_class" option to customize compilation.
     * @param array         $options  Options array
     */
    public function __construct($name, $callable = null, array $options = array())
    {
        $this->name = $name;
        $this->callable = $callable;
        $this->options = array_merge(array(
            'is_variadic' => false,
            'node_class' => 'Twig_Node_Expression_Test',
            'deprecated' => false,
            'alternative' => null,
        ), $options);
    }

    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns the callable to execute for this test.
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

    public function isVariadic()
    {
        return $this->options['is_variadic'];
    }

    public function isDeprecated()
    {
        return (bool) $this->options['deprecated'];
    }

    public function getDeprecatedVersion()
    {
        return $this->options['deprecated'];
    }

    public function getAlternative()
    {
        return $this->options['alternative'];
    }
}
