<?php

namespace Twig\Tests\Util;

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\LoaderInterface;
use Twig\TwigFunction;
use Twig\Util\DeprecationCollector;

class DeprecationCollectorTest extends TestCase
{
    /**
     * @requires PHP 5.3
     */
    public function testCollect()
    {
        $twig = new Environment($this->createMock(LoaderInterface::class));
        $twig->addFunction(new TwigFunction('deprec', [$this, 'deprec'], ['deprecated' => '1.1']));

        $collector = new DeprecationCollector($twig);
        $deprecations = $collector->collect(new Twig_Tests_Util_Iterator());

        $this->assertEquals(['Twig Function "deprec" is deprecated since version 1.1 in deprec.twig at line 1.'], $deprecations);
    }

    public function deprec()
    {
    }
}

class Twig_Tests_Util_Iterator implements \IteratorAggregate
{
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator([
            'ok.twig' => '{{ foo }}',
            'deprec.twig' => '{{ deprec("foo") }}',
        ]);
    }
}
