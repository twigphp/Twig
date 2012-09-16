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
 * Represents a template filter coded as a PHP callable.
 *
 * @package    twig
 * @author     Fabien Potencier <fabien@symfony.com>
 */
class Twig_Filter_Callable extends Twig_Filter
{
    protected $name;

    public function __construct($name, array $options = array())
    {
        parent::__construct($options);

        $this->name = $name;
    }

    public function compile()
    {
        if (version_compare(PHP_VERSION, '5.4.0', '<')) {
            return sprintf('call_user_func_array($this->env->callables[\'__filters__%s\'], array', $this->name);
        } else {
            return sprintf('$this->env->callables[\'__filters__%s\']', $this->name);
        }
    }
}
