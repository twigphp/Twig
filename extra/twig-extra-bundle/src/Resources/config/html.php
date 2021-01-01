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

use Twig\Extra\Html\HtmlExtension;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set('twig.extension.html', HtmlExtension::class)
            ->tag('twig.extension')
    ;
};
