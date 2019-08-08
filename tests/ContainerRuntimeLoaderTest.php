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

use Twig\RuntimeLoader\ContainerRuntimeLoader;

class ContainerRuntimeLoaderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @requires PHP 5.3
     */
    public function testLoad()
    {
        $container = $this->createMock('Psr\Container\ContainerInterface');
        $container->expects($this->once())->method('has')->with('stdClass')->willReturn(true);
        $container->expects($this->once())->method('get')->with('stdClass')->willReturn(new \stdClass());

        $loader = new ContainerRuntimeLoader($container);

        $this->assertInstanceOf('stdClass', $loader->load('stdClass'));
    }

    /**
     * @requires PHP 5.3
     */
    public function testLoadUnknownRuntimeReturnsNull()
    {
        $container = $this->createMock('Psr\Container\ContainerInterface');
        $container->expects($this->once())->method('has')->with('Foo');
        $container->expects($this->never())->method('get');

        $loader = new ContainerRuntimeLoader($container);
        $this->assertNull($loader->load('Foo'));
    }
}
