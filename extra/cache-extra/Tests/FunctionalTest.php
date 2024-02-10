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

use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Contracts\Cache\CacheInterface;
use Twig\Environment;
use Twig\Error\SyntaxError;
use Twig\Extra\Cache\CacheExtension;
use Twig\Extra\Cache\CacheRuntime;
use Twig\Loader\ArrayLoader;
use Twig\RuntimeLoader\RuntimeLoaderInterface;

class FunctionalTest extends TestCase
{
    public function testIsCached()
    {
        $cache = new ArrayAdapter();
        $twig = $this->createEnvironment(['index' => '{% cache "city;v1" %}{{- city -}}{% endcache %}'], $cache);

        $this->assertSame('Paris', $twig->render('index', ['city' => 'Paris']));
        $value = $cache->get('city;v1', function () { throw new \RuntimeException('Key should be in the cache'); });
        $this->assertSame('Paris', $value);
    }

    public function testTtlNoArgs()
    {
        $twig = $this->createEnvironment(['index' => '{% cache "ttl_no_args" ttl() %}{% endcache %}']);
        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage('The "ttl" modifier takes exactly one argument (0 given) in "index" at line 1.');
        $twig->render('index');
    }

    public function testTtlTooManyArgs()
    {
        $twig = $this->createEnvironment(['index' => '{% cache "ttl_too_many_args" ttl(0, 1) %}{% endcache %}']);
        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage('The "ttl" modifier takes exactly one argument (2 given) in "index" at line 1.');
        $twig->render('index');
    }

    public function testTagsNoArgs()
    {
        $twig = $this->createEnvironment(['index' => '{% cache "tags_no_args" tags() %}{% endcache %}']);
        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage('The "tags" modifier takes exactly one argument (0 given) in "index" at line 1.');
        $twig->render('index');
    }

    public function testTagsTooManyArgs()
    {
        $twig = $this->createEnvironment(['index' => '{% cache "tags_too_many_args" tags(["foo"], 1) %}{% endcache %}']);
        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage('The "tags" modifier takes exactly one argument (2 given) in "index" at line 1.');
        $twig->render('index');
    }

    private function createEnvironment(array $templates, ?ArrayAdapter $cache = null): Environment
    {
        $twig = new Environment(new ArrayLoader($templates));
        $cache = $cache ?? new ArrayAdapter();
        $twig->addExtension(new CacheExtension());
        $twig->addRuntimeLoader(new class($cache) implements RuntimeLoaderInterface {
            private $cache;

            public function __construct(CacheInterface $cache)
            {
                $this->cache = $cache;
            }

            public function load($class)
            {
                if (CacheRuntime::class === $class) {
                    return new CacheRuntime($this->cache);
                }
            }
        });

        return $twig;
    }
}
