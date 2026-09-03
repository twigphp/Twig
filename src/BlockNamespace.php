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
final class BlockNamespace
{
    /** @var array<string, array{Template, string}> */
    private array $blocks = [];

    /**
     * @param array<string, BlockDefinition> $definitions
     */
    public function __construct(
        private array $definitions,
    ) {
        foreach ($definitions as $name => $definition) {
            $this->blocks[$name] = $definition->toLegacy();
        }
    }

    public function has(string $name): bool
    {
        return isset($this->definitions[$name]);
    }

    public function get(string $name): ?BlockDefinition
    {
        return $this->definitions[$name] ?? null;
    }

    /**
     * @return string[]
     */
    public function getNames(): array
    {
        return array_keys($this->definitions);
    }

    /**
     * @return array<string, array{Template, string}>
     */
    public function toLegacy(): array
    {
        return $this->blocks;
    }
}
