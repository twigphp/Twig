<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 * (c) Armin Ronacher
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Node\Expression\Unary;

use Twig\Compiler;
use Twig\Node\Expression\AbstractExpression;
use Twig\Node\Node;

abstract class AbstractUnary extends AbstractExpression
{
    public function __construct(Node $node, int $lineno)
    {
        parent::__construct(['node' => $node], ['with_parentheses' => false], $lineno);
    }

    public function wrapInParentheses(): void
    {
        $this->setAttribute('with_parentheses', true);
    }

    public function compile(Compiler $compiler): void
    {
        if ($this->hasExplicitParentheses()) {
            $compiler->raw('(');
        } else {
            $compiler->raw(' ');
        }
        $this->operator($compiler);
        $compiler->subcompile($this->getNode('node'));
        if ($this->hasExplicitParentheses()) {
            $compiler->raw(')');
        }
    }

    abstract public function operator(Compiler $compiler): Compiler;
}
