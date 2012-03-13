<?php
/*
 * This file is part of Twig.
 *
 * (c) 2012 Badlee Oshimin
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Represents a break node.
 *
 * @package    twig
 * @author     Badlee Oshimin <badlee.oshimin@gmail.com>
 */
class Twig_Node_Break extends Twig_Node
{
    public function __construct($lineno, $tag){
        parent::__construct(array(), array(), $lineno, $tag);
    }

    /**
     * Compiles the node to PHP.
     *
     * @param Twig_Compiler A Twig_Compiler instance
     */
    public function compile(Twig_Compiler $compiler)
    {
    	
    	$compiler->write("break;\n");
    }
}