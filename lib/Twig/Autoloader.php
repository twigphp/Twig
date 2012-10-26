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
     */
    static public function register()
    {
        ini_set('unserialize_callback_func', 'spl_autoload_call');
        // No point in new self() here as we use a static objectless function
        spl_autoload_register(array(__CLASS__, 'autoload'));
    }

    /**
     * Handles autoloading of classes.
     *
     * @param string $class A class name.
     */
    static public function autoload($class)
    {
        if (0 !== strpos($class, 'Twig')) {
            return;
        }
        $class = preg_replace('~^Twig[^_]*_~i', null, $class); // pop the prefix and
        // seek the autoloaded file in the current directory, easily
        if (is_file($file = dirname(__FILE__).'/'.str_replace(array('_', "\0"), array('/', ''), $class).'.php')) {
            require $file;
        }
    }
}
