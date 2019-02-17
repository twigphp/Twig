<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\Template;
use Twig\TwigFunction;

final class Twig_Extension_StringLoader extends AbstractExtension
{
    public function getFunctions()
    {
        return [
            new TwigFunction('template_from_string', 'twig_template_from_string', ['needs_environment' => true]),
        ];
    }
}

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

class_alias('Twig_Extension_StringLoader', 'Twig\Extension\StringLoaderExtension', false);
