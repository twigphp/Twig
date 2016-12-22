<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Lazy loads the runtime implementations for a Twig element.
 *
 * @author Robin Chalas <robin.chalas@gmail.com>
 */
class Twig_FactoryRuntimeLoader implements Twig_RuntimeLoaderInterface
{
    private $map;

    /**
     * @param array $map An array of format [classname => factory callable]
     */
    public function __construct(array $map = array())
    {
        $this->map = $map;
    }

    /**
     * {@inheritdoc}
     */
    public function load($class)
    {
        if (isset($this->map[$class])) {
            $runtimeFactory = $this->map[$class];

            return $runtimeFactory();
        }
    }
}
