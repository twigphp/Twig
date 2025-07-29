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

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
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

        $this->addCommonMarkConfiguration($rootNode);

        return $treeBuilder;
    }

    /**
     * Full configuration from {@link https://commonmark.thephpleague.com/2.7/configuration}.
     */
    private function addCommonMarkConfiguration(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->children()
                ->arrayNode('commonmark')
                    ->ignoreExtraKeys(false)
                    ->children()
                        ->arrayNode('renderer')
                            ->info('Array of options for rendering HTML.')
                            ->children()
                                ->scalarNode('block_separator')->end()
                                ->scalarNode('inner_separator')->end()
                                ->scalarNode('soft_break')->end()
                            ->end()
                        ->end()
                        ->enumNode('html_input')
                            ->info('How to handle HTML input.')
                            ->values(['strip', 'allow', 'escape'])
                            ->end()
                        ->booleanNode('allow_unsafe_links')
                            ->info('Remove risky link and image URLs by setting this to false.')
                            ->defaultTrue()
                            ->end()
                        ->integerNode('max_nesting_level')
                            ->info('The maximum nesting level for blocks.')
                            ->defaultValue(\PHP_INT_MAX)
                            ->end()
                        ->integerNode('max_delimiters_per_line')
                            ->info('The maximum number of strong/emphasis delimiters per line.')
                            ->defaultValue(\PHP_INT_MAX)
                            ->end()
                        ->arrayNode('slug_normalizer')
                            ->info('Array of options for configuring how URL-safe slugs are created.')
                            ->children()
                                ->variableNode('instance')->end()
                                ->integerNode('max_length')->defaultValue(255)->end()
                                ->variableNode('unique')->end()
                            ->end()
                        ->end()
                        ->arrayNode('commonmark')
                            ->info('Array of options for configuring the CommonMark core extension.')
                            ->children()
                                ->booleanNode('enable_em')->defaultTrue()->end()
                                ->booleanNode('enable_strong')->defaultTrue()->end()
                                ->booleanNode('use_asterisk')->defaultTrue()->end()
                                ->booleanNode('use_underscore')->defaultTrue()->end()
                                ->arrayNode('unordered_list_markers')
                                    ->scalarPrototype()->end()
                                    ->defaultValue([['-', '*', '+']])->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }
}
