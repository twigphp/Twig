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
 * Empty class for Twig 1.x compatibility.
 */
class Twig_SimpleFunction extends Twig_Function
{
    /**
     * {@inheritdoc}
     */
    public function __construct(string $name, $callable = null, array $options = array())
    {
        @trigger_error('"Twig_SimpleFunction" is deprecated in favor of "Twig_Function" and will be removed in Twig 3.0.', E_USER_DEPRECATED);

        parent::__construct($name, $callable, $options);
    }
}
