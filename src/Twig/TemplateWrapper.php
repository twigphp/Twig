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

/**
 * Exposes a template to userland.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
final class TemplateWrapper
{
    private $env;
    private $template;

    /**
     * This method is for internal use only and should never be called
     * directly (use Twig\Environment::load() instead).
     *
     * @internal
     */
    public function __construct(Environment $env, Template $template)
    {
        $this->env = $env;
        $this->template = $template;
    }

    /**
     * Renders the template.
     *
     * @param array $context An array of parameters to pass to the template
     *
     * @return string The rendered template
     */
    public function render($context = [])
    {
        // using func_get_args() allows to not expose the blocks argument
        // as it should only be used by internal code
        return $this->template->render($context, \func_get_args()[1] ?? []);
    }

    /**
     * Displays the template.
     *
     * @param array $context An array of parameters to pass to the template
     */
    public function display($context = [])
    {
        // using func_get_args() allows to not expose the blocks argument
        // as it should only be used by internal code
        $this->template->display($context, \func_get_args()[1] ?? []);
    }

    /**
     * Checks if a block is defined.
     *
     * @param string $name    The block name
     * @param array  $context An array of parameters to pass to the template
     *
     * @return bool
     */
    public function hasBlock($name, $context = [])
    {
        return $this->template->hasBlock($name, $context);
    }

    /**
     * Returns defined block names in the template.
     *
     * @param array $context An array of parameters to pass to the template
     *
     * @return string[] An array of defined template block names
     */
    public function getBlockNames($context = [])
    {
        return $this->template->getBlockNames($context);
    }

    /**
     * Renders a template block.
     *
     * @param string $name    The block name to render
     * @param array  $context An array of parameters to pass to the template
     *
     * @return string The rendered block
     */
    public function renderBlock($name, $context = [])
    {
        $context = $this->env->mergeGlobals($context);
        $level = ob_get_level();
        ob_start();
        try {
            $this->template->displayBlock($name, $context);
        } catch (\Throwable $e) {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }

            throw $e;
        }

        return ob_get_clean();
    }

    /**
     * Displays a template block.
     *
     * @param string $name    The block name to render
     * @param array  $context An array of parameters to pass to the template
     */
    public function displayBlock($name, $context = [])
    {
        $this->template->displayBlock($name, $this->env->mergeGlobals($context));
    }

    /**
     * @return Source
     */
    public function getSourceContext()
    {
        return $this->template->getSourceContext();
    }
}
