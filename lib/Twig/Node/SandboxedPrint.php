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
 * Twig_Node_SandboxedPrint adds a check for the __toString() method
 * when the variable is an object and the sandbox is activated.
 *
 * When there is a simple Print statement, like {{ article }},
 * and if the sandbox is enabled, we need to check that the __toString()
 * method is allowed if 'article' is an object.
 *
 * @package    twig
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 */
class Twig_Node_SandboxedPrint extends Twig_Node_Print
{
    public function __construct(Twig_Node_Expression $expr, $lineno, $tag = null)
    {
        parent::__construct($expr, $lineno, $tag);
    }

    /**
     * Compiles the node to PHP.
     *
     * @param Twig_Compiler A Twig_Compiler instance
     */
    public function compile(Twig_Compiler $compiler)
    {
        $compiler
            ->addDebugInfo($this)
            ->write('if (is_object(')
            ->raw('$_tmp = ')
            ->subcompile($this->removeNodeFilter($this->getNode('expr')))
            ->raw(')) {'."\n")
            ->indent()
            ->write('$this->env->getExtension(\'sandbox\')->checkMethodAllowed(')
            ->raw('$_tmp, \'__toString\');'."\n")
            ->outdent()
            ->write('}'."\n")
        ;

        parent::compile($compiler);
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
