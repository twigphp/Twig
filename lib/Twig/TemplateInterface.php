<?php

/*
 * This file is part of Twig.
 *
 * (c) 2009 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Interface implemented by all compiled templates.
 *
 * @package twig
 * @author  Fabien Potencier <fabien.potencier@symfony-project.com>
 */
interface Twig_TemplateInterface
{
    const ANY_CALL    = 'any';
    const ARRAY_CALL  = 'array';
    const METHOD_CALL = 'method';

    /**
     * Renders the template with the given context and returns it as string.
     *
     * @param array $context An array of parameters to pass to the template
     *
     * @return string The rendered template
     */
    function render(array $context);

    /**
     * Displays the template with the given context.
     *
     * @param array $context An array of parameters to pass to the template
     */
    function display(array $context);

    /**
     * Returns the bound environment for this template.
     *
     * @return Twig_Environment The current environment
     */
    function getEnvironment();
}
