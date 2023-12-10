<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Extra\CssInliner;

/**
 * @internal
 *
 * @deprecated since Twig 3.9.0
 */
function twig_inline_css(string $body, string ...$css): string
{
    trigger_deprecation('twig/cssinliner-extra', '3.9.0', 'Using the internal "%s" function is deprecated.', __FUNCTION__);

    return CssInlinerExtension::inlineCss($body, ...$css);
}
