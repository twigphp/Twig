<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Twig_Node_SandboxedPrint adds a check for the __toString() method
 * when the variable is an object and the sandbox is activated.
 *
 * When there is a simple Print statement, like {{ article }},
 * and if the sandbox is enabled, we need to check that the __toString()
 * method is allowed if 'article' is an object.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Twig_Node_SandboxedPrint extends \Twig\Node\PrintNode
{
    public function compile(\Twig\Compiler $compiler)
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
     * @return \Twig\Node\Node
     */
    protected function removeNodeFilter(\Twig\Node\Node $node)
    {
        if ($node instanceof \Twig\Node\Expression\FilterExpression) {
            return $this->removeNodeFilter($node->getNode('node'));
        }

        return $node;
    }
}

class_alias('Twig_Node_SandboxedPrint', 'Twig\Node\SandboxedPrintNode', false);
