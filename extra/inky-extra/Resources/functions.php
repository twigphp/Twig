<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Extra\Inky;

/**
 * @internal
 *
 * @deprecated since Twig 3.9.0
 */
function twig_inky(string $body): string
{
    trigger_deprecation('twig/inky-extra', '3.9.0', 'Using the internal "%s" function is deprecated.', __FUNCTION__);

    return InkyExtension::inky($body);
}
