<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Extra\Cache\Tests;

use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Twig\Extra\Cache\CacheExtension;
use Twig\Extra\Cache\CacheRuntime;
use Twig\RuntimeLoader\RuntimeLoaderInterface;
use Twig\Test\IntegrationTestCase;

class IntegrationTest extends IntegrationTestCase
{
    public function getExtensions()
    {
        return [
            new CacheExtension(),
        ];
    }

    protected function getRuntimeLoaders()
    {
        return [
            new class implements RuntimeLoaderInterface {
                public function load(string $class): ?object
                {
                    return CacheRuntime::class === $class ? new CacheRuntime(new ArrayAdapter()) : null;
                }
            },
        ];
    }

    protected static function getFixturesDirectory(): string
    {
        return __DIR__.'/Fixtures/';
    }
}
