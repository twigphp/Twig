<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Extra\TwigExtraBundle;

use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\KernelInterface;
use Twig\Extra\TwigExtraBundle\DependencyInjection\Compiler\MissingExtensionSuggestorPass;
use Twig\Extra\TwigExtraBundle\DependencyInjection\Compiler\TwigCachePoolPass;

if (method_exists(KernelInterface::class, 'getShareDir')) {
    class TwigExtraBundle extends Bundle
    {
        public function build(ContainerBuilder $container): void
        {
            parent::build($container);

            $container->addCompilerPass(new MissingExtensionSuggestorPass());
            // priority 64 so that it runs before Symfony's CachePoolPass (priority 32)
            $container->addCompilerPass(new TwigCachePoolPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 64);
        }
    }
} else {
    class TwigExtraBundle extends Bundle
    {
        /** @return void */
        public function build(ContainerBuilder $container)
        {
            parent::build($container);

            $container->addCompilerPass(new MissingExtensionSuggestorPass());
            // priority 64 so that it runs before Symfony's CachePoolPass (priority 32)
            $container->addCompilerPass(new TwigCachePoolPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 64);
        }
    }
}
