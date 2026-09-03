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
final class BlockDefinition
{
    public function __construct(
        private string $name,
        private Template $template,
        private string $method,
    ) {
    }

    public static function fromLegacy(string $name, mixed $block): self
    {
        if (!\is_array($block) || !isset($block[0], $block[1]) || !$block[0] instanceof Template || !\is_string($block[1])) {
            throw new \LogicException('A block must be a method on a \Twig\Template instance.');
        }

        return new self($name, $block[0], $block[1]);
    }

    public function getTemplate(): Template
    {
        return $this->template;
    }

    /**
     * @return array{Template, string}
     */
    public function toLegacy(): array
    {
        return [$this->template, $this->method];
    }

    /**
     * @return iterable<scalar|\Stringable|null>
     */
    public function yield(array $context, array $blocks): iterable
    {
        yield from $this->template->yieldBlock($this->name, $context, $blocks);
    }
}
