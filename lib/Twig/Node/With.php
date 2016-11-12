<?php

/*
 * This file is part of Twig.
 *
 * (c) 2009 Fabien Potencier
 * (c) 2013 Berny Cantos
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Represents a nested scope
 *
 * @author Berny Cantos <be@rny.cc>
 */
class Twig_Node_With extends Twig_Node
{
    public function __construct(Twig_NodeInterface $content, Twig_NodeInterface $setter, $lineno, $tag = null)
    {
        parent::__construct(array('content' => $content, 'setter' => $setter), array(), $lineno, $tag);
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
            ->write("\$context['_parent'] = (array) \$context;\n")
            ->subcompile($this->getNode('setter'))
            ->subcompile($this->getNode('content'))
            ->write("\$context = \$context['_parent'];\n")
        ;
    }
}
