<?php

/*
 * This file is part of Twig.
 *
 * (c) 2010 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Twig_Node_SandboxedPrintMultiple adds a check for the __toString() method
 * when the variable is an object and the sandbox is activated.
 *
 * When there is a simple Print statement, like {{ article }},
 * and if the sandbox is enabled, we need to check that the __toString()
 * method is allowed if 'article' is an object.
 *
 * @package    twig
 * @author     Vladimir Beloborodov <redhead.ru@gmail.com>
 */
class Twig_Node_SandboxedPrintMultiple extends Twig_Node_PrintMultiple
{
    public function __construct(Twig_Node_PrintMultiple $node, $tag = null)
    {
        parent::__construct($node->nodes, $tag);
    }

    /**
     * Compiles the node to PHP.
     *
     * @param Twig_Compiler A Twig_Compiler instance
     */
    public function compile(Twig_Compiler $compiler)
    {
        foreach ($this as $idx => $node) {
            if (!$idx) {
                $compiler
                    ->addDebugInfo($node)
                    ->write('echo ')
                    ->indent()
                    ;
            }
            else {
                $compiler
                    ->raw(",\n")
                    ->addDebugInfo($node)
                    ->addIndentation()
                    ;
            }
            if ($node instanceof Twig_Node_Text) {
                $compiler->string($node->getAttribute('data'));
            }
            else {
                $compiler
                    ->raw('$this->env->getExtension(\'sandbox\')->ensureToStringAllowed(')
                    ->subcompile($node)
                    ->raw(')')
                    ;
            }
        }
        if (isset($node)) { // if $this->nodes is not empty, $node gets set in the foreach loop
            $compiler
                ->outdent()
                ->raw(";\n")
                ;
        }
    }

    /**
     * Removes node filters.
     *
     * This is mostly needed when another visitor adds filters (like the escaper one).
     *
     * @param Twig_Node $node A Node
     */
    protected function removeNodeFilter($node)
    {
        if ($node instanceof Twig_Node_Expression_Filter) {
            return $this->removeNodeFilter($node->getNode('node'));
        }

        return $node;
    }
}
