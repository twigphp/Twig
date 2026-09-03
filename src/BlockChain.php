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

use Twig\Error\RuntimeError;

/**
 * Composes blocks from several templates without composing their bodies or macros.
 */
final class BlockChain
{
    private BlockNamespace $blocks;
    private Template $template;

    /**
     * @param iterable<string|TemplateWrapper> $templates Templates ordered from highest to lowest precedence
     */
    public function __construct(
        private Environment $env,
        iterable $templates,
        array $context = [],
    ) {
        $resolution = new BlockResolutionContext($env, $context + $env->getGlobals());
        $definitions = [];

        foreach ($templates as $template) {
            if (\is_string($template)) {
                $template = $env->load($template);
            }
            if (!$template instanceof TemplateWrapper) {
                throw new \TypeError(\sprintf('Block chain templates must be strings or "%s" instances, "%s" given.', TemplateWrapper::class, get_debug_type($template)));
            }

            $current = $template->unwrap($env)->freezeLineage($resolution);
            $this->template ??= $current;
            $seen = [];
            do {
                $id = spl_object_id($current);
                if (isset($seen[$id])) {
                    throw new \LogicException(\sprintf('Circular template inheritance detected while building a block chain from "%s".', $current->getTemplateName()));
                }
                $seen[$id] = true;

                foreach ($current->getBlocks() as $name => $block) {
                    if (!isset($definitions[$name])) {
                        $definition = BlockDefinition::fromLegacy($name, $block);
                        $resolution->assertOwns($definition->getTemplate());
                        $definitions[$name] = $definition;
                    }
                }
            } while (false !== $current = $resolution->getParent($current));
        }

        if (!isset($this->template)) {
            throw new \InvalidArgumentException('A block chain requires at least one template.');
        }

        $this->blocks = new BlockNamespace($definitions);
    }

    public function hasBlock(string $name): bool
    {
        return $this->blocks->has($name);
    }

    /**
     * @return string[]
     */
    public function getBlockNames(): array
    {
        return $this->blocks->getNames();
    }

    /**
     * @return iterable<scalar|\Stringable|null>
     */
    public function streamBlock(string $name, array $context = []): iterable
    {
        yield from $this->getBlock($name)->yield($context, $this->blocks->toLegacy());
    }

    public function renderBlock(string $name, array $context = []): string
    {
        $context += $this->env->getGlobals();
        if ($this->env->useYield()) {
            $content = '';
            foreach ($this->streamBlock($name, $context) as $data) {
                $content .= $data;
            }

            return $content;
        }

        $level = ob_get_level();
        if ($this->env->isDebug()) {
            ob_start();
        } else {
            ob_start(static function () { return ''; });
        }
        try {
            $this->displayBlock($name, $context);
        } catch (\Throwable $e) {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }

            throw $e;
        }

        return ob_get_clean();
    }

    public function displayBlock(string $name, array $context = []): void
    {
        $context += $this->env->getGlobals();
        foreach ($this->streamBlock($name, $context) as $data) {
            echo $data;
        }
    }

    private function getBlock(string $name): BlockDefinition
    {
        if (null !== $definition = $this->blocks->get($name)) {
            return $definition;
        }

        throw new RuntimeError(\sprintf('Block "%s" on template "%s" does not exist.', $name, $this->template->getTemplateName()), -1, $this->template->getSourceContext());
    }
}
