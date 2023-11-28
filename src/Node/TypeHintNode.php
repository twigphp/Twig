<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Node;

use Twig\Compiler;

/**
 * Represents a type-hint node.
 *
 * @author Joshua Behrens <code@joshua-behrens.de>
 */
class TypeHintNode extends Node
{
    public function __construct(string $name, string $type, int $lineno, string $tag = null)
    {
        parent::__construct([], ['name' => $name, 'type' => $type], $lineno, $tag);
    }

    public function compile(Compiler $compiler): void
    {
        $compiler->addDebugInfo($this);
    }
}
