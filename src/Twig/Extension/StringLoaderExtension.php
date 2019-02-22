<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Extension
{

use Twig\Template;
use Twig\TwigFunction;

final class StringLoaderExtension extends AbstractExtension
{
    public function getFunctions()
    {
        return [
            new TwigFunction('template_from_string', 'twig_template_from_string', ['needs_environment' => true]),
        ];
    }
}

}

namespace
{

use Twig\Environment;

/**
 * Loads a template from a string.
 *
 *     {{ include(template_from_string("Hello {{ name }}")) }}
 *
 * @param string $template A template as a string or object implementing __toString()
 *
 * @return Template
 */
function twig_template_from_string(Environment $env, $template)
{
    return $env->createTemplate((string) $template);
}

}
