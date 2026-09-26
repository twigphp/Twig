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
 * Represents an else node in a for loop.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class ForElseNode extends Node
{
    public function __construct(Node $body, int $lineno)
    {
        parent::__construct(['body' => $body], ['with_condition' => false], $lineno);
    }

    public function compile(Compiler $compiler): void
    {
        $compiler
            ->addDebugInfo($this)
            ->write($this->getAttribute('with_condition') ? "if (!\$iterated) {\n" : "if (0 === \$iterator->getIndex0()) {\n")
            ->indent()
            ->subcompile($this->getNode('body'))
            ->outdent()
            ->write("}\n")
        ;
    }
}
