<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Tests\Runtime;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Extension\CoreExtension;
use Twig\Loader\ArrayLoader;
use Twig\Runtime\LoopContext;
use Twig\Runtime\LoopIterator;
use Twig\Source;

class LoopContextTest extends TestCase
{
    public function testGettersAreTheMethodsReadForTheAttributes(): void
    {
        $env = new Environment(new ArrayLoader());
        $source = new Source('', 'index');
        $loop = new LoopContext(new LoopIterator(['a', 'b', 'c']), ['foo' => 'bar'], [], static fn () => yield, 0);

        foreach (LoopContext::GETTERS as $attribute => $getter) {
            $this->assertSame($loop->$getter(), CoreExtension::getAttribute($env, $source, $loop, $attribute), $attribute);
        }

        $getters = [];
        foreach ((new \ReflectionClass(LoopContext::class))->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if (!$method->isConstructor() && !$method->getNumberOfParameters()) {
                $getters[] = $method->getName();
            }
        }
        sort($getters);
        $expected = array_values(LoopContext::GETTERS);
        sort($expected);

        $this->assertSame($expected, $getters);
    }
}
