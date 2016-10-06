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
 * Represents a strict node.
 *
 * Enables strict mode for the included block.
 *
 * @author Lars Strojny <lstrojny@php.net>
 */
class Twig_Node_Strict extends Twig_Node
{
    public function __construct(Twig_NodeInterface $body, $lineno, $tag = 'strict')
    {
        parent::__construct(array('body' => $body), array(), $lineno, $tag);
    }

    /**
     * Compiles the node to PHP.
     *
     * @param Twig_Compiler $compiler A Twig_Compiler instance
     */
    public function compile(Twig_Compiler $compiler)
    {
        if ($compiler->getEnvironment()->isStrictVariables()) {
            $compiler->subcompile($this->getNode('body'));

            return;
        }

        $compiler->getEnvironment()->enableStrictVariables();
        $compiler
            ->write("\$this->env->enableStrictVariables();\n")
            ->subcompile($this->getNode('body'))
            ->write("\$this->env->disableStrictVariables();\n")
        ;
        $compiler->getEnvironment()->disableStrictVariables();
    }
}
