<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once dirname(__FILE__).'/bootstrap.php';

class Twig_Tests_AutoloaderTest extends PHPUnit_Framework_TestCase
{
    public function testAutoload()
    {
        $this->assertFalse(class_exists('FooBarFoo'), '->autoload() does not try to load classes that does not begin with Twig');

        $autoloader = new Twig_Autoloader();
        $this->assertTrue($autoloader->autoload('Twig_Parser'), '->autoload() returns true if it is able to load a class');
        $this->assertFalse($autoloader->autoload('Foo'), '->autoload() returns false if it is not able to load a class');
    }
}
