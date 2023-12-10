<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Tests\Extension;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Extension\DebugExtension;
use Twig\Loader\ArrayLoader;

/**
 * @group legacy
 */
class LegacyDebugFunctionsTest extends TestCase
{
    public function testDump()
    {
        $env = new Environment(new ArrayLoader());

        $this->assertSame(DebugExtension::dump($env, 'Foo'), twig_var_dump($env, 'Foo'));
    }
}
