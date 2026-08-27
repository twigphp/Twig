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

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

if (!method_exists(ContainerBuilder::class, 'getAutoconfiguredAttributes')) {
    class MissingExtensionSuggestorPass implements CompilerPassInterface
    {
        public function process(ContainerBuilder $container): void
        {
            if (!$container->getParameter('kernel.debug')) {
                return;
            }
            $twigDefinition = $container->getDefinition('twig');
            $twigDefinition
                ->addMethodCall('registerUndefinedFilterCallback', [[new Reference('twig.missing_extension_suggestor'), 'suggestFilter']])
                ->addMethodCall('registerUndefinedFunctionCallback', [[new Reference('twig.missing_extension_suggestor'), 'suggestFunction']])
                ->addMethodCall('registerUndefinedTokenParserCallback', [[new Reference('twig.missing_extension_suggestor'), 'suggestTag']])
            ;
        }
    }
} else {
    class MissingExtensionSuggestorPass implements CompilerPassInterface
    {
        /** @return void */
        public function process(ContainerBuilder $container)
        {
            if (!$container->getParameter('kernel.debug')) {
                return;
            }
            $twigDefinition = $container->getDefinition('twig');
            $twigDefinition
                ->addMethodCall('registerUndefinedFilterCallback', [[new Reference('twig.missing_extension_suggestor'), 'suggestFilter']])
                ->addMethodCall('registerUndefinedFunctionCallback', [[new Reference('twig.missing_extension_suggestor'), 'suggestFunction']])
                ->addMethodCall('registerUndefinedTokenParserCallback', [[new Reference('twig.missing_extension_suggestor'), 'suggestTag']])
            ;
        }
    }
}
