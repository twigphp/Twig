<?php

/*
 * This file is part of Twig.
 *
 * (c) 2009 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Autoloads Twig classes.
 *
 * @package twig
 * @author  Fabien Potencier <fabien@symfony.com>
 */
class Twig_Autoloader
{
    /**
     * Registers Twig_Autoloader as an SPL autoloader.
     * 
     * @param boolean $prepend (optional) Whether to add the autoloader to
     * the start of the stack. Defaults to false so the autoloader will be
     * appended to the stack.
     */
    public static function register($prepend = false)
    {
        ini_set('unserialize_callback_func', 'spl_autoload_call');
        spl_autoload_register(array(new self, 'autoload'), true, $prepend);
    }

    /**
     * Handles autoloading of classes.
     *
     * @param string $class A class name.
     */
    public static function autoload($class)
    {
        if (0 !== strpos($class, 'Twig')) {
            return;
        }

        if (is_file($file = dirname(__FILE__).'/../'.str_replace(array('_', "\0"), array('/', ''), $class).'.php')) {
            require $file;
        }
    }
}
