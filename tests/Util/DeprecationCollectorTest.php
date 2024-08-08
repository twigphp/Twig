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
use Twig\Loader\ArrayLoader;
use Twig\TwigFunction;
use Twig\Util\DeprecationCollector;

class DeprecationCollectorTest extends TestCase
{
    /**
     * @requires PHP 5.3
     */
    public function testCollect()
    {
        $twig = new Environment(new ArrayLoader());
        $twig->addFunction(new TwigFunction('deprec', [$this, 'deprec'], ['deprecated' => '1.1', 'deprecating_package' => 'foo/bar']));

        $collector = new DeprecationCollector($twig);
        $deprecations = $collector->collect(new Iterator());

        $this->assertEquals(['Since foo/bar 1.1: Twig Function "deprec" is deprecated in deprec.twig at line 1.'], $deprecations);
    }

    public function deprec()
    {
    }
}

class Iterator implements \IteratorAggregate
{
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator([
            'ok.twig' => '{{ foo }}',
            'deprec.twig' => '{{ deprec("foo") }}',
        ]);
    }
}
