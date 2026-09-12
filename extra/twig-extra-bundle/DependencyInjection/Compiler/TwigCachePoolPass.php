<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Extra\TwigExtraBundle\DependencyInjection\Compiler;

use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Drops the "twig.cache" TagAwareAdapter when the pool it decorates is already tag aware.
 *
 * Wrapping a tag aware adapter in a TagAwareAdapter makes every read a miss, so the
 * decorator must go when "cache.app" uses a natively tag aware adapter, the same way
 * Symfony aliases "cache.app.taggable" to "cache.app" instead of decorating it.
 *
 * @internal
 */
final class TwigCachePoolPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('twig.cache') || !$container->hasDefinition('.twig.cache.inner')) {
            return;
        }

        $definition = $container->getDefinition('.twig.cache.inner');
        $class = $definition->getClass();

        while (null === $class && $definition instanceof ChildDefinition) {
            $parent = $definition->getParent();

            if (!$container->hasDefinition($parent) && !$container->hasAlias($parent)) {
                return;
            }

            $definition = $container->findDefinition($parent);
            $class = $definition->getClass();
        }

        if (!is_a($class ?? '', TagAwareAdapterInterface::class, true)) {
            return;
        }

        $container->removeDefinition('twig.cache');
        $container->setAlias('twig.cache', '.twig.cache.inner');
    }
}
