<?php

/*
 * This file is part of Twig.
 *
 * (c) 2012 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Represents a template test.
 *
 * @package    twig
 * @author     Fabien Potencier <fabien@symfony.com>
 */
abstract class Twig_Test implements Twig_TestInterface, Twig_TestCallableInterface
{
    protected $options;
    protected $arguments = array();

    public function __construct(array $options = array())
    {
        $this->options = array_merge(array(
            'callable' => null,
        ), $options);
    }

    public function getCallable()
    {
        return $this->options['callable'];
    }
}
