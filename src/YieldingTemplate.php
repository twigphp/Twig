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

use Twig\Error\Error;
use Twig\Error\RuntimeError;

/**
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @internal
 */
abstract class YieldingTemplate extends Template
{
    /**
     * @return iterable<string>
     */
    public function yield(array $context, array $blocks = []): iterable
    {
        $context = $this->env->mergeGlobals($context);
        $blocks = array_merge($this->blocks, $blocks);

        try {
            yield from $this->doDisplay($context, $blocks);
        } catch (Error $e) {
            if (!$e->getSourceContext()) {
                $e->setSourceContext($this->getSourceContext());
            }

            // this is mostly useful for \Twig\Error\LoaderError exceptions
            // see \Twig\Error\LoaderError
            if (-1 === $e->getTemplateLine()) {
                $e->guess();
            }

            throw $e;
        } catch (\Throwable $e) {
            $e = new RuntimeError(sprintf('An exception has been thrown during the rendering of a template ("%s").', $e->getMessage()), -1, $this->getSourceContext(), $e);
            $e->guess();

            throw $e;
        }
    }

    public function render(array $context): string
    {
        $content = '';
        foreach ($this->yield($context) as $data) {
            $content .= $data;
        }

        return $content;
    }

    public function display(array $context, array $blocks = []): void
    {
        foreach ($this->yield($context, $blocks) as $data) {
            echo $data;
        }
    }

    /**
     * @return iterable<string>
     */
    public function yieldBlock($name, array $context, array $blocks = [], $useBlocks = true, Template $templateContext = null)
    {
        if ($useBlocks && isset($blocks[$name])) {
            $template = $blocks[$name][0];
            $block = $blocks[$name][1];
        } elseif (isset($this->blocks[$name])) {
            $template = $this->blocks[$name][0];
            $block = $this->blocks[$name][1];
        } else {
            $template = null;
            $block = null;
        }

        // avoid RCEs when sandbox is enabled
        if (null !== $template && !$template instanceof Template) {
            throw new \LogicException('A block must be a method on a \Twig\Template instance.');
        }

        if (null !== $template) {
            try {
                yield from $template->$block($context, $blocks);
            } catch (Error $e) {
                if (!$e->getSourceContext()) {
                    $e->setSourceContext($template->getSourceContext());
                }

                // this is mostly useful for \Twig\Error\LoaderError exceptions
                // see \Twig\Error\LoaderError
                if (-1 === $e->getTemplateLine()) {
                    $e->guess();
                }

                throw $e;
            } catch (\Throwable $e) {
                $e = new RuntimeError(sprintf('An exception has been thrown during the rendering of a template ("%s").', $e->getMessage()), -1, $template->getSourceContext(), $e);
                $e->guess();

                throw $e;
            }
        } elseif (false !== $parent = $this->getParent($context)) {
            /** @var YieldingTemplate $parent */
            yield from $parent->yieldBlock($name, $context, array_merge($this->blocks, $blocks), false, $templateContext ?? $this);
        } elseif (isset($blocks[$name])) {
            throw new RuntimeError(sprintf('Block "%s" should not call parent() in "%s" as the block does not exist in the parent template "%s".', $name, $blocks[$name][0]->getTemplateName(), $this->getTemplateName()), -1, $blocks[$name][0]->getSourceContext());
        } else {
            throw new RuntimeError(sprintf('Block "%s" on template "%s" does not exist.', $name, $this->getTemplateName()), -1, ($templateContext ?? $this)->getSourceContext());
        }
    }

    public function renderBlock($name, array $context, array $blocks = [], $useBlocks = true)
    {
        $content = '';
        foreach ($this->yieldBlock($name, $context, $blocks, $useBlocks) as $data) {
            $content .= $data;
        }

        return $content;
    }

    /**
     * Yields a parent block.
     *
     * This method is for internal use only and should never be called
     * directly.
     *
     * @param string $name    The block name to display from the parent
     * @param array  $context The context
     * @param array  $blocks  The current set of blocks
     *
     * @return iterable<string>
     */
    public function yieldParentBlock($name, array $context, array $blocks = [])
    {
        if (isset($this->traits[$name])) {
            yield from $this->traits[$name][0]->yieldBlock($name, $context, $blocks, false);
        } elseif (false !== $parent = $this->getParent($context)) {
            $parent = $parent->unwrap();
            /** @var YieldingTemplate $parent */
            yield from $parent->yieldBlock($name, $context, $blocks, false);
        } else {
            throw new RuntimeError(sprintf('The template has no parent and no traits defining the "%s" block.', $name), -1, $this->getSourceContext());
        }
    }

    public function renderParentBlock($name, array $context, array $blocks = [])
    {
        $content = '';
        foreach ($this->yieldParentBlock($name, $context, $blocks) as $data) {
            $content .= $data;
        }

        return $content;
    }

    public function displayBlock($name, array $context, array $blocks = [], $useBlocks = true, Template $templateContext = null)
    {
        foreach ($this->yieldBlock($name, $context, $blocks, $useBlocks, $templateContext) as $data) {
            echo $data;
        }
    }

    public function displayParentBlock($name, array $context, array $blocks = [])
    {
        foreach ($this->yieldParentBlock($name, $context, $blocks) as $data) {
            echo $data;
        }
    }

    protected function displayWithErrorHandling(array $context, array $blocks = [])
    {
        throw new RuntimeError(sprintf('Calling "%s" is not supported as "use_yield" is set to "true".', __METHOD__), -1, $this->getSourceContext());
    }
}
