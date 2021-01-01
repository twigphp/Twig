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

use Twig\Extra\TwigExtraBundle\MissingExtensionSuggestor;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set('twig.missing_extension_suggestor', MissingExtensionSuggestor::class)
    ;
};
