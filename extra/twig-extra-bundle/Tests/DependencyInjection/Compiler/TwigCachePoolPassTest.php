<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Extra\TwigExtraBundle\Tests\DependencyInjection\Compiler;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\RedisTagAwareAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Extra\TwigExtraBundle\Tests\Fixture\CacheKernel;

class TwigCachePoolPassTest extends TestCase
{
    /** @var CacheKernel[] */
    private array $kernels = [];

    protected function tearDown(): void
    {
        foreach ($this->kernels as $kernel) {
            (new Filesystem())->remove($kernel->getTempDir());
        }

        $this->kernels = [];
    }

    public function testThePoolIsDecoratedWhenTheAppAdapterIsNotTagAware(): void
    {
        $pools = $this->compileFor(null);

        $this->assertSame(TagAwareAdapter::class, $pools['twig.cache']);
        $this->assertSame(FilesystemAdapter::class, $pools['.twig.cache.inner']);
        $this->assertNotSame($pools['cache.app.namespace'], $pools['.twig.cache.inner.namespace']);
    }

    public function testThePoolIsNotDecoratedWhenTheAppAdapterIsTagAware(): void
    {
        $pools = $this->compileFor('cache.adapter.redis_tag_aware');

        $this->assertSame('@.twig.cache.inner', $pools['twig.cache']);
        $this->assertSame(RedisTagAwareAdapter::class, $pools['.twig.cache.inner']);
        $this->assertNotSame($pools['cache.app.namespace'], $pools['.twig.cache.inner.namespace']);
    }

    private function compileFor(?string $cacheAdapter): array
    {
        $this->kernels[] = $kernel = new CacheKernel($cacheAdapter);
        $kernel->boot();

        return $kernel->getContainer()->getParameter('twig_extra.test.cache_pools');
    }
}
