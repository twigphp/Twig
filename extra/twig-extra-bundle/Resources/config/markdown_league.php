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

use League\CommonMark\CommonMarkConverter;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Twig\Extra\Markdown\LeagueMarkdown;
use Twig\Extra\TwigExtraBundle\LeagueCommonMarkConverterFactory;

return static function (ContainerConfigurator $container) {
    $service = \function_exists('Symfony\Component\DependencyInjection\Loader\Configurator\service') ? 'Symfony\Component\DependencyInjection\Loader\Configurator\service' : 'Symfony\Component\DependencyInjection\Loader\Configurator\ref';
    $container->services()
        ->set('twig.markdown.league_common_mark_converter_factory', LeagueCommonMarkConverterFactory::class)
        ->args([new TaggedIteratorArgument('twig.markdown.league_extension')])

        ->set('twig.markdown.league_common_mark_converter', CommonMarkConverter::class)
        ->factory($service('twig.markdown.league_common_mark_converter_factory'))

        ->set('twig.markdown.default', LeagueMarkdown::class)
        ->args([$service('twig.markdown.league_common_mark_converter')])
    ;
};
