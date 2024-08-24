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

use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Node\Expression\AbstractExpression;

/**
 * Represents a assert node.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
#[YieldReady]
class AssertNode extends Node
{
    public function __construct(AbstractExpression $expr, int $lineno, ?string $tag = null)
    {
        parent::__construct(['expr' => $expr], [], $lineno, $tag);
    }

    public function compile(Compiler $compiler): void
    {
        $compiler->addDebugInfo($this);
        // TODO disable in production(?)

        $compiler
            ->write(' if (!')
            ->subcompile($this->getNode('expr'))
            ->raw(") {\n")
            ->indent()
            ->write("throw new RuntimeError('Failed');\n")
            ->outdent()
            ->raw("}\n")
        ;
    }
}
