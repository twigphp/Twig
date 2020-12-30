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

use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Twig\Extra\Cache\CacheExtension;
use Twig\Extra\Cache\CacheRuntime;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set('twig.extension.cache', CacheExtension::class)
            ->tag('twig.extension')

        ->set('twig.runtime.cache', CacheRuntime::class)
            ->args([
                service('twig.cache.default'),
            ])
            ->tag('twig.runtime')

        ->alias('twig.cache.default', TagAwareCacheInterface::class)
    ;
};
