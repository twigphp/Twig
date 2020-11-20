<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Extension {
use Twig\TwigFunction;

final class StringLoaderExtension extends AbstractExtension
{
    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('template_from_string', 'twig_template_from_string', ['needs_environment' => true]),
        ];
    }
}
}

namespace {
use Twig\Environment;
use Twig\TemplateWrapper;

    /**
     * Loads a template from a string.
     *
     *     {{ include(template_from_string("Hello {{ name }}")) }}
     *
     * @param Environment $env
     * @param string $template A template as a string or object implementing __toString()
     * @param string|null $name An optional name of the template to be used in error messages
     *
     * @return TemplateWrapper
     * @throws \Twig\Error\Error
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
function twig_template_from_string(Environment $env, $template, string $name = null): TemplateWrapper
{
    return $env->createTemplate((string) $template, $name);
}
}
