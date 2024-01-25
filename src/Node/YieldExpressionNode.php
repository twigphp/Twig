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
 * Represents a node that outputs an expression.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class YieldExpressionNode extends PrintNode
{
    public function compile(Compiler $compiler): void
    {
        $compiler
            ->addDebugInfo($this)
            ->write('yield ')
            ->subcompile($this->getNode('expr'))
            ->raw(";\n")
        ;
    }
}
