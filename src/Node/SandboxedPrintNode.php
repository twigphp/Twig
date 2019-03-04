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
use Twig\Node\Expression\FilterExpression;

/**
 * \Twig_Node_SandboxedPrint adds a check for the __toString() method
 * when the variable is an object and the sandbox is activated.
 *
 * When there is a simple Print statement, like {{ article }},
 * and if the sandbox is enabled, we need to check that the __toString()
 * method is allowed if 'article' is an object.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class SandboxedPrintNode extends PrintNode
{
    public function compile(Compiler $compiler)
    {
        $compiler
            ->addDebugInfo($this)
            ->write('echo $this->env->getExtension(\'\Twig\Extension\SandboxExtension\')->ensureToStringAllowed(')
            ->subcompile($this->getNode('expr'))
            ->raw(");\n")
        ;
    }

    /**
     * Removes node filters.
     *
     * This is mostly needed when another visitor adds filters (like the escaper one).
     *
     * @return Node
     */
    protected function removeNodeFilter(Node $node)
    {
        if ($node instanceof FilterExpression) {
            return $this->removeNodeFilter($node->getNode('node'));
        }

        return $node;
    }
}

class_alias('Twig\Node\SandboxedPrintNode', 'Twig_Node_SandboxedPrint');
