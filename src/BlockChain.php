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
    /** @var list<Template> */
    private array $templates = [];

    /** @var list<Template> */
    private array $lineage = [];

    /** @var array<string, array{Template, string}> */
    private array $blocks = [];

    /**
     * Whether the lineage can no longer move, making every further resolution pointless.
     */
    private bool $fixed = false;

    /**
     * @param iterable<string|TemplateWrapper> $templates Templates ordered from highest to lowest precedence
     * @param array<string, mixed>             $context   Default variables used to resolve dynamic parent expressions
     */
    public function __construct(
        private Environment $env,
        iterable $templates,
        private array $context = [],
    ) {
        foreach ($templates as $template) {
            if (\is_string($template)) {
                $template = $env->load($template);
            }
            if (!$template instanceof TemplateWrapper) {
                throw new \TypeError(\sprintf('Block chain templates must be strings or "%s" instances, "%s" given.', TemplateWrapper::class, get_debug_type($template)));
            }

            $template = $template->unwrap();
            if (!$template->isOwnedBy($env)) {
                throw new \LogicException('A block chain cannot contain templates from different Twig environments.');
            }

            $this->templates[] = $template;
        }

        if (!$this->templates) {
            throw new \InvalidArgumentException('A block chain requires at least one template.');
        }
    }

    /**
     * @param array<string, mixed> $context
     */
    public function hasBlock(string $name, array $context = []): bool
    {
        $blocks = $this->fixed ? $this->blocks : $this->resolveBlocks($context + $this->context + $this->env->getGlobals());

        return isset($blocks[$name]);
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return string[]
     */
    public function getBlockNames(array $context = []): array
    {
        return array_keys($this->fixed ? $this->blocks : $this->resolveBlocks($context + $this->context + $this->env->getGlobals()));
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return iterable<scalar|\Stringable|null>
     */
    public function streamBlock(string $name, array $context = []): iterable
    {
        $context += $this->context + $this->env->getGlobals();
        $blocks = $this->fixed ? $this->blocks : $this->resolveBlocks($context);
        if (!isset($blocks[$name])) {
            $this->throwUnknownBlock($name);
        }

        yield from $this->templates[0]->yieldBlock($name, $context, $blocks);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function renderBlock(string $name, array $context = []): string
    {
        $context += $this->context + $this->env->getGlobals();
        $blocks = $this->fixed ? $this->blocks : $this->resolveBlocks($context);
        if (!isset($blocks[$name])) {
            $this->throwUnknownBlock($name);
        }

        return $this->templates[0]->renderBlock($name, $context, $blocks);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function displayBlock(string $name, array $context = []): void
    {
        $context += $this->context + $this->env->getGlobals();
        $blocks = $this->fixed ? $this->blocks : $this->resolveBlocks($context);
        if (!isset($blocks[$name])) {
            $this->throwUnknownBlock($name);
        }

        $this->templates[0]->displayBlock($name, $context, $blocks);
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return array<string, array{Template, string}>
     */
    private function resolveBlocks(array $context): array
    {
        [$lineage, $this->fixed] = $this->resolveLineage($context);

        if ($lineage === $this->lineage) {
            return $this->blocks;
        }

        $blocks = [];
        foreach ($lineage as $template) {
            foreach ($template->getBlocks() as $name => $block) {
                $blocks[$name] ??= $block;
            }
        }

        $this->lineage = $lineage;

        return $this->blocks = $blocks;
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return array{list<Template>, bool}
     */
    private function resolveLineage(array $context): array
    {
        $lineage = [];
        $fixed = true;

        foreach ($this->templates as $template) {
            $seen = [];
            do {
                if (isset($seen[$id = spl_object_id($template)])) {
                    throw new \LogicException(\sprintf('Circular template inheritance detected while building a block chain from "%s".', $template->getTemplateName()));
                }
                $seen[$id] = true;
                $lineage[] = $template;

                $parent = $template->getParent($context);
                $fixed = $fixed && $template->hasFixedParent();

                // a dynamic parent expression can evaluate to a template from another environment
                $template = $parent instanceof TemplateWrapper ? $parent->unwrap() : $parent;
                if (false !== $template && !$template->isOwnedBy($this->env)) {
                    throw new \LogicException('A block chain cannot contain templates from different Twig environments.');
                }
            } while (false !== $template);
        }

        return [$lineage, $fixed];
    }

    private function throwUnknownBlock(string $name): never
    {
        throw new RuntimeError(\sprintf('Block "%s" on template "%s" does not exist.', $name, $this->templates[0]->getTemplateName()), -1, $this->templates[0]->getSourceContext());
    }
}
