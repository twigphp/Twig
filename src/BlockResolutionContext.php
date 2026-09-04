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
    /** @var \SplObjectStorage<Template, Template|false> */
    private \SplObjectStorage $parents;

    /** @var \SplObjectStorage<Template, Template> */
    private \SplObjectStorage $frozen;

    /** @var \SplObjectStorage<Template, true> */
    private \SplObjectStorage $freezing;

    public function __construct(
        private Environment $env,
        private array $context,
    ) {
        $this->parents = new \SplObjectStorage();
        $this->frozen = new \SplObjectStorage();
        $this->freezing = new \SplObjectStorage();
    }

    public function getParent(Template $template): Template|false
    {
        if ($this->parents->offsetExists($template)) {
            return $this->parents[$template];
        }

        $parent = $template->getParent($this->context);
        if ($parent instanceof TemplateWrapper) {
            $parent = $parent->unwrap($this->env);
        } elseif ($parent instanceof Template) {
            $this->assertOwns($parent);
        }

        return $this->parents[$template] = $parent;
    }

    public function assertOwns(Template $template): void
    {
        if (!$template->isOwnedBy($this->env)) {
            throw new \LogicException('A block chain cannot contain templates from different Twig environments.');
        }
    }

    public function isFrozen(Template $template): bool
    {
        return $this->frozen->offsetExists($template);
    }

    public function getFrozen(Template $template): Template
    {
        return $this->frozen[$template];
    }

    public function setFrozen(Template $template, Template $frozen): void
    {
        $this->frozen[$template] = $frozen;
    }

    public function beginFreeze(Template $template): void
    {
        if ($this->freezing->offsetExists($template)) {
            throw new \LogicException(\sprintf('Circular template inheritance detected while building a block chain from "%s".', $template->getTemplateName()));
        }

        $this->freezing[$template] = true;
    }

    public function endFreeze(Template $template): void
    {
        $this->freezing->offsetUnset($template);
    }
}
