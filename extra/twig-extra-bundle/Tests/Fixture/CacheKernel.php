<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Extra\TwigExtraBundle\Tests\Fixture;

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\FrameworkBundle\Test\NotificationAssertionsTrait;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Twig\Extra\TwigExtraBundle\TwigExtraBundle;

/**
 * Compiles a container for a given "framework.cache.app" adapter and exposes how the
 * Twig cache pool ended up being wired in the "twig_extra.test.cache_pools" parameter.
 */
class CacheKernel extends BaseKernel
{
    use MicroKernelTrait;

    private string $dir;

    public function __construct(private ?string $cacheAdapter = null)
    {
        $this->dir = sys_get_temp_dir().'/twig-extra-bundle/'.bin2hex(random_bytes(6));

        parent::__construct('test', false);
    }

    public function getCacheDir(): string
    {
        return $this->dir.'/cache';
    }

    public function getLogDir(): string
    {
        return $this->dir.'/log';
    }

    public function getProjectDir(): string
    {
        return __DIR__;
    }

    public function getTempDir(): string
    {
        return $this->dir;
    }

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new TwigBundle();
        yield new TwigExtraBundle();
    }

    protected function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new class implements CompilerPassInterface {
            public function process(ContainerBuilder $container): void
            {
                $pools = [];
                foreach (['twig.cache', '.twig.cache.inner', 'cache.app'] as $id) {
                    if ($container->hasAlias($id)) {
                        $pools[$id] = '@'.$container->getAlias($id);

                        continue;
                    }

                    $definition = $container->getDefinition($id);
                    $pools[$id] = $definition->getClass();

                    // cache adapters take their namespace as their first string argument
                    foreach ($definition->getArguments() as $argument) {
                        if (\is_string($argument)) {
                            $pools[$id.'.namespace'] = $argument;

                            break;
                        }
                    }
                }

                $container->setParameter('twig_extra.test.cache_pools', $pools);
            }
        }, PassConfig::TYPE_BEFORE_REMOVING, -1000);
    }

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader): void
    {
        $config = [
            'secret' => 'S3CRET',
            'router' => ['utf8' => true],
            'http_method_override' => false,
            'php_errors' => [
                'log' => true,
            ],
        ];

        // the "handle_all_throwables" option was introduced in FrameworkBundle 6.2 (and so was the NotificationAssertionsTrait)
        if (trait_exists(NotificationAssertionsTrait::class)) {
            $config['handle_all_throwables'] = true;
        }

        if (null !== $this->cacheAdapter) {
            $config['cache'] = ['app' => $this->cacheAdapter];
        }

        $c->loadFromExtension('framework', $config);
        $c->loadFromExtension('twig', [
            'default_path' => __DIR__.'/views',
        ]);
    }

    protected function configureRoutes($routes): void
    {
    }
}
