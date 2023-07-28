<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Extra\TwigExtraBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Twig\Extra\TwigExtraBundle\Extensions;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('twig_extra');
        $rootNode = $treeBuilder->getRootNode();

        foreach (Extensions::getClasses() as $name => $class) {
            $rootNode
                ->children()
                    ->arrayNode($name)
                        ->{class_exists($class) ? 'canBeDisabled' : 'canBeEnabled'}()
                    ->end()
                ->end()
            ;
        }

        return $treeBuilder;
    }
}
