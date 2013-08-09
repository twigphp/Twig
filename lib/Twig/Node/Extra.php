<?php

/*
 * This file is part of Twig.
 *
 * (c) 2013 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * This node can be used with a Node Visitor to add some methods to the
 * template.
 *
 * @author Grégoire Pineau <lyrixx@lyrixx.info>
 */
class Twig_Node_Extra extends Twig_Node
{
    /**
     * Compiles the node to PHP.
     *
     * @param Twig_Compiler A Twig_Compiler instance
     */
    public function compile(Twig_Compiler $compiler)
    {
        foreach ($this->nodes as $node) {
            $node->compile($compiler);
        }
    }
}
