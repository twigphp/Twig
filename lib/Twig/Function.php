<?php

/*
 * This file is part of Twig.
 *
 * (c) 2010 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Defines a new Twig function.
 *
 * @package    twig
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 */
class Twig_Function extends Exception
{
    protected $object;
    protected $method;

    public function __construct($object, $method)
    {
        $this->object = $object;
        $this->method = $method;
    }

    public function getObject()
    {
        return $this->object;
    }

    public function getMethod()
    {
        return $this->method;
    }
}
