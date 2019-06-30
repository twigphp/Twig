<?php

namespace Twig\Tests;

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class AutoloaderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @group legacy
     */
    public function testAutoload()
    {
        $this->assertFalse(class_exists('FooBarFoo'), '->autoload() does not try to load classes that does not begin with Twig');

        $autoloader = new \Twig_Autoloader();
        $this->assertNull($autoloader->autoload('Foo'), '->autoload() returns false if it is not able to load a class');
    }
}
