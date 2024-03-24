<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Twig\TypeHint;

/**
 * Describes a stack of possible types for a variable.
 *
 * @author Joshua Behrens <code@joshua-behrens.de>
 */
class ContextStack
{
    /**
     * First/major layer non-sharing stacks (with-node e.g. tagged as only)
     * Second/minor layer for sharing stacks (with-node e.g. not tagged as only)
     *
     * @var list<list<array<string, list<TypeInterface>>>>
     */
    private array $variables = [[]];

    public function getVariableType(string $name): ?TypeInterface
    {
        $result = [];

        foreach ($this->variables[0] as $types) {
            foreach ($types[$name] ?? [] as $type) {
                $result[] = $type;
            }
        }

        return TypeFactory::createTypeFromCollection($result);
    }

    public function addVariableType(string $name, TypeInterface $type): void
    {
        $this->variables[0][0][$name][] = $type;
    }

    public function pushMajorStack(): void
    {
        \array_unshift($this->variables, []);
    }

    public function popMajorStack(): void
    {
        \array_shift($this->variables);
    }

    public function pushMinorStack(): void
    {
        \array_unshift($this->variables[0], []);
    }

    public function popMinorStack(): void
    {
        \array_shift($this->variables[0]);
    }
}
