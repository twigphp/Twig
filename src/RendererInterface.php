<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig;

interface RendererInterface
{

    /**
     * Renders a template.
     *
     * @param string  $name    The template name
     * @param array   $context An array of parameters to pass to the template
     *
     * @return string The rendered template
     */
    public function render($name, array $context = []);

}
