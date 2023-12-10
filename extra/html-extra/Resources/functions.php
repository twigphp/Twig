<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Twig\Extra\Html\HtmlExtension;

/**
 * @internal
 *
 * @deprecated since Twig 3.9.0
 */
function twig_html_classes(...$args): string
{
    trigger_deprecation('twig/html-extra', '3.9.0', 'Using the internal "%s" function is deprecated.', __FUNCTION__);

    return HtmlExtension::htmlClasses(...$args);
}
