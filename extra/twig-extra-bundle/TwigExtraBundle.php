<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Extra\TwigExtraBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Twig\Extra\TwigExtraBundle\DependencyInjection\Compiler\MissingExtensionSuggestorPass;

class TwigExtraBundle extends Bundle
{
    /** @return void */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new MissingExtensionSuggestorPass());
    }
}
