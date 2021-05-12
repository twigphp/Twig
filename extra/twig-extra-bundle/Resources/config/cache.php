<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Twig\Extra\Cache\CacheExtension;
use Twig\Extra\Cache\CacheRuntime;

return static function (ContainerConfigurator $container) {
    $service = \function_exists('Symfony\Component\DependencyInjection\Loader\Configurator\service') ? 'Symfony\Component\DependencyInjection\Loader\Configurator\service' : 'Symfony\Component\DependencyInjection\Loader\Configurator\ref';
    $container->services()
        ->set('twig.extension.cache', CacheExtension::class)
            ->tag('twig.extension')

        ->set('twig.runtime.cache', CacheRuntime::class)
            ->args([
                $service('twig.cache'),
            ])
            ->tag('twig.runtime')

        ->set('twig.cache', TagAwareAdapter::class)
            ->args([
                $service('.twig.cache.inner'),
            ])

        ->set('.twig.cache.inner')
            ->parent('cache.app')
            ->tag('cache.pool', ['name' => 'twig.cache'])

        ->alias(TagAwareCacheInterface::class.' $twigCache', 'twig.cache')
        ->alias(CacheInterface::class.' $twigCache', '.twig.cache.inner')
        ->alias(CacheItemPoolInterface::class.' $twigCache', '.twig.cache.inner')
    ;
};
