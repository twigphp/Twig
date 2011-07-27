<?php

/*
 * This file is part of Twig.
 *
 * (c) 2009 Fabien Potencier
 * (c) 2009 Armin Ronacher
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Represents a block call node.
 *
 * @package    twig
 * @author     Fabien Potencier <fabien@symfony.com>
 */
class Twig_Node_OptimizedBlockReference extends Twig_Node_BlockReference implements Twig_NodeOutputInterface
{
    public function __construct(Twig_NodeInterface $name, $lineno, $tag = null)
    {
        call_user_func('Twig_Node::__construct', array('name' => $name), array(), $lineno, $tag);
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
            ->write("\$this->displayBlock(")
            ->subcompile($this->getNode('name'))
            ->raw(", \$context, \$blocks);\n")
        ;
    }
}
