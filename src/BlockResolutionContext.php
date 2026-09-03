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
 * @internal
 */
final class BlockResolutionContext
{
    /** @var array<int, Template|false> */
    private array $parents = [];

    /** @var array<int, Template> */
    private array $frozen = [];

    /** @var array<int, true> */
    private array $freezing = [];

    public function __construct(
        private Environment $env,
        private array $context,
    ) {
    }

    public function getParent(Template $template): Template|false
    {
        $id = spl_object_id($template);
        if (isset($this->parents[$id]) || \array_key_exists($id, $this->parents)) {
            return $this->parents[$id];
        }

        $parent = $template->getParent($this->context);
        if ($parent instanceof TemplateWrapper) {
            $parent = $parent->unwrap($this->env);
        } elseif ($parent instanceof Template) {
            $this->assertOwns($parent);
        }

        return $this->parents[$id] = $parent;
    }

    public function assertOwns(Template $template): void
    {
        if (!$template->isOwnedBy($this->env)) {
            throw new \LogicException('A block chain cannot contain templates from different Twig environments.');
        }
    }

    public function isFrozen(Template $template): bool
    {
        return isset($this->frozen[spl_object_id($template)]);
    }

    public function getFrozen(Template $template): Template
    {
        return $this->frozen[spl_object_id($template)];
    }

    public function setFrozen(Template $template, Template $frozen): void
    {
        $this->frozen[spl_object_id($template)] = $frozen;
    }

    public function beginFreeze(Template $template): void
    {
        $id = spl_object_id($template);
        if (isset($this->freezing[$id])) {
            throw new \LogicException(\sprintf('Circular template inheritance detected while building a block chain from "%s".', $template->getTemplateName()));
        }

        $this->freezing[$id] = true;
    }

    public function endFreeze(Template $template): void
    {
        unset($this->freezing[spl_object_id($template)]);
    }
}
