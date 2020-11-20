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
    /**
     * @var Environment
     */
    private $env;
    /**
     * @var Template
     */
    private $template;

    /**
     * This method is for internal use only and should never be called
     * directly (use Twig\Environment::load() instead).
     *
     * @param Environment $env
     * @param Template $template
     * @internal
     */
    public function __construct(Environment $env, Template $template)
    {
        $this->env = $env;
        $this->template = $template;
    }

    /**
     * @param array $context
     * @return string
     * @throws \Throwable
     */
    public function render(array $context = []): string
    {
        // using func_get_args() allows to not expose the blocks argument
        // as it should only be used by internal code
        return $this->template->render($context, \func_get_args()[1] ?? []);
    }

    /**
     * @param array $context
     */
    public function display(array $context = [])
    {
        // using func_get_args() allows to not expose the blocks argument
        // as it should only be used by internal code
        $this->template->display($context, \func_get_args()[1] ?? []);
    }

    /**
     * @param string $name
     * @param array $context
     * @return bool
     * @throws Error\Error
     * @throws Error\LoaderError
     * @throws Error\RuntimeError
     */
    public function hasBlock(string $name, array $context = []): bool
    {
        return $this->template->hasBlock($name, $context);
    }

    /**
     * @param array $context
     * @return string[] An array of defined template block names
     * @throws Error\Error
     * @throws Error\LoaderError
     * @throws Error\RuntimeError
     */
    public function getBlockNames(array $context = []): array
    {
        return $this->template->getBlockNames($context);
    }

    /**
     * @param string $name
     * @param array $context
     * @return string
     * @throws Error\Error
     * @throws Error\LoaderError
     * @throws Error\RuntimeError
     * @throws \Throwable
     */
    public function renderBlock(string $name, array $context = []): string
    {
        $context = $this->env->mergeGlobals($context);
        $level = ob_get_level();
        if ($this->env->isDebug()) {
            ob_start();
        } else {
            ob_start(function () { return ''; });
        }
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
     * @param string $name
     * @param array $context
     * @throws Error\Error
     * @throws Error\LoaderError
     * @throws Error\RuntimeError
     */
    public function displayBlock(string $name, array $context = [])
    {
        $this->template->displayBlock($name, $this->env->mergeGlobals($context));
    }

    /**
     * @return Source
     */
    public function getSourceContext(): Source
    {
        return $this->template->getSourceContext();
    }

    /**
     * @return string
     */
    public function getTemplateName(): string
    {
        return $this->template->getTemplateName();
    }

    /**
     * @internal
     *
     * @return Template
     */
    public function unwrap()
    {
        return $this->template;
    }
}
