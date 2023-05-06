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
use Twig\Environment;

class MissingExtensionSuggestorPass implements CompilerPassInterface
{
    /** @return void */
    public function process(ContainerBuilder $container)
    {
        if ($container->getParameter('kernel.debug')) {
            $twigDefinition = $container->getDefinition('twig');
            $twigDefinition
                ->addMethodCall('registerUndefinedFilterCallback', [[new Reference('twig.missing_extension_suggestor'), 'suggestFilter']])
                ->addMethodCall('registerUndefinedFunctionCallback', [[new Reference('twig.missing_extension_suggestor'), 'suggestFunction']])
            ;

            // this method was added in Twig 3.2
            if (method_exists(Environment::class, 'registerUndefinedTokenParserCallback')) {
                $twigDefinition->addMethodCall('registerUndefinedTokenParserCallback', [[new Reference('twig.missing_extension_suggestor'), 'suggestTag']]);
            }
        }
    }
}
