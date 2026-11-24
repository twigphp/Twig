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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Contracts\Cache\CacheInterface;
use Twig\Environment;
use Twig\Error\SyntaxError;
use Twig\Extension\SandboxExtension;
use Twig\Extra\Cache\CacheExtension;
use Twig\Extra\Cache\CacheRuntime;
use Twig\Loader\ArrayLoader;
use Twig\RuntimeLoader\RuntimeLoaderInterface;
use Twig\Sandbox\SecurityNotAllowedMethodError;
use Twig\Sandbox\SecurityPolicy;

class FunctionalTest extends TestCase
{
    public function testIsCached(): void
    {
        $cache = new ArrayAdapter();
        $twig = $this->createEnvironment(['index' => '{% cache "city;v1" %}{{- city -}}{% endcache %}'], $cache);

        $this->assertSame('Paris', $twig->render('index', ['city' => 'Paris']));
        $value = $cache->get('city;v1', static function () { throw new \RuntimeException('Key should be in the cache'); });
        $this->assertSame('Paris', $value);
    }

    public function testTtlNoArgs(): void
    {
        $twig = $this->createEnvironment(['index' => '{% cache "ttl_no_args" ttl() %}{% endcache %}']);
        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage('The "ttl" modifier takes exactly one argument (0 given) in "index" at line 1.');
        $twig->render('index');
    }

    public function testTtlTooManyArgs(): void
    {
        $twig = $this->createEnvironment(['index' => '{% cache "ttl_too_many_args" ttl(0, 1) %}{% endcache %}']);
        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage('The "ttl" modifier takes exactly one argument (2 given) in "index" at line 1.');
        $twig->render('index');
    }

    public function testTagsNoArgs(): void
    {
        $twig = $this->createEnvironment(['index' => '{% cache "tags_no_args" tags() %}{% endcache %}']);
        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage('The "tags" modifier takes exactly one argument (0 given) in "index" at line 1.');
        $twig->render('index');
    }

    public function testTagsTooManyArgs(): void
    {
        $twig = $this->createEnvironment(['index' => '{% cache "tags_too_many_args" tags(["foo"], 1) %}{% endcache %}']);
        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage('The "tags" modifier takes exactly one argument (2 given) in "index" at line 1.');
        $twig->render('index');
    }

    /**
     * @dataProvider provideStringCoercedExpressions
     */
    #[DataProvider('provideStringCoercedExpressions')]
    public function testSandboxChecksStringCoercedExpressions(string $template): void
    {
        $twig = $this->createEnvironment(['index' => $template], new TagAwareAdapter(new ArrayAdapter()));
        $twig->addExtension(new SandboxExtension(new SecurityPolicy(['cache'], ['raw']), true));

        $this->expectException(SecurityNotAllowedMethodError::class);
        $twig->render('index', ['obj' => new class {
            public function __toString(): string
            {
                return 'key';
            }
        }]);
    }

    public static function provideStringCoercedExpressions(): iterable
    {
        yield 'key' => ['{% cache obj %}{% endcache %}'];
        yield 'tag' => ['{% cache "key" tags(obj) %}{% endcache %}'];
        yield 'tag in a list' => ['{% cache "key" tags([obj]) %}{% endcache %}'];
    }

    private function createEnvironment(array $templates, ?CacheInterface $cache = null): Environment
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

            public function load(string $class): ?object
            {
                return CacheRuntime::class === $class ? new CacheRuntime($this->cache) : null;
            }
        });

        return $twig;
    }
}
