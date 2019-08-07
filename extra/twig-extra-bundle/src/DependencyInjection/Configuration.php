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
use Twig\Extra\Html\HtmlExtension;
use Twig\Extra\Intl\IntlExtension;
use Twig\Extra\Markdown\MarkdownExtension;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('twig_extra');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('html')
                    ->{class_exists(HtmlExtension::class) ? 'canBeDisabled' : 'canBeEnabled'}()
                ->end()
            ->end()
        ;

        $rootNode
            ->children()
                ->arrayNode('markdown')
                    ->{class_exists(MarkdownExtension::class) ? 'canBeDisabled' : 'canBeEnabled'}()
                ->end()
            ->end()
        ;

        $rootNode
        ->children()
            ->arrayNode('intl')
                ->{class_exists(IntlExtension::class) ? 'canBeDisabled' : 'canBeEnabled'}()
            ->end()
        ->end()
    ;

        return $treeBuilder;
    }
}
