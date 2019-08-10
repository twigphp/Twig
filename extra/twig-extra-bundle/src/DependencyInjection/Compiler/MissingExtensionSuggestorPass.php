<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Extra\TwigExtraBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class MissingExtensionSuggestorPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if ($container->getParameter('kernel.debug')) {
            $container->getDefinition('twig')
                ->addMethodCall('registerUndefinedFilterCallback', [[new Reference('twig.missing_extension_suggestor'), 'suggestFilter']])
                ->addMethodCall('registerUndefinedFunctionCallback', [[new Reference('twig.missing_extension_suggestor'), 'suggestFunction']])
            ;
        }
    }
}
