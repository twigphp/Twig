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
    /** @var array<string, array{Template, string}> */
    private array $blocks;
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
        $blocks = [];
        $owners = [];

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
                    if (isset($blocks[$name])) {
                        continue;
                    }
                    if (!\is_array($block) || !isset($block[0], $block[1]) || !$block[0] instanceof Template || !\is_string($block[1])) {
                        throw new \LogicException('A block must be a method on a \Twig\Template instance.');
                    }

                    $ownerId = spl_object_id($block[0]);
                    if (!isset($owners[$ownerId])) {
                        $resolution->assertOwns($block[0]);
                        $owners[$ownerId] = true;
                    }
                    $blocks[$name] = $block;
                }
            } while (false !== $current = $resolution->getParent($current));
        }

        if (!isset($this->template)) {
            throw new \InvalidArgumentException('A block chain requires at least one template.');
        }

        $this->blocks = $blocks;
    }

    public function hasBlock(string $name): bool
    {
        return isset($this->blocks[$name]);
    }

    /**
     * @return string[]
     */
    public function getBlockNames(): array
    {
        return array_keys($this->blocks);
    }

    /**
     * @return iterable<scalar|\Stringable|null>
     */
    public function streamBlock(string $name, array $context = []): iterable
    {
        yield from $this->getBlock($name)->yieldBlock($name, $context, $this->blocks);
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

    private function getBlock(string $name): Template
    {
        if (isset($this->blocks[$name])) {
            return $this->blocks[$name][0];
        }

        throw new RuntimeError(\sprintf('Block "%s" on template "%s" does not exist.', $name, $this->template->getTemplateName()), -1, $this->template->getSourceContext());
    }
}
