<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Extra\Markdown;

/**
 * @internal
 *
 * @deprecated since Twig 3.9.0
 */
function html_to_markdown(string $body, array $options = []): string
{
    trigger_deprecation('twig/intl-extra', '3.9.0', 'Using the internal "%s" function is deprecated.', __FUNCTION__);

    return MarkdownExtension::htmlToMarkdown($body, $options);
}
