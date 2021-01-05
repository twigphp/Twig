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

use Twig\Extra\Markdown\DefaultMarkdown;
use Twig\Extra\Markdown\MarkdownExtension;
use Twig\Extra\Markdown\MarkdownRuntime;

return static function (ContainerConfigurator $container) {
    $service = function_exists('Symfony\Component\DependencyInjection\Loader\Configurator\service') ? 'Symfony\Component\DependencyInjection\Loader\Configurator\service' : 'Symfony\Component\DependencyInjection\Loader\Configurator\ref';
    $container->services()
        ->set('twig.extension.markdown', MarkdownExtension::class)
            ->tag('twig.extension')

        ->set('twig.runtime.markdown', MarkdownRuntime::class)
            ->args([
                $service('twig.markdown.default'),
            ])
            ->tag('twig.runtime')

        ->set('twig.markdown.default', DefaultMarkdown::class)
    ;
};
