<?php

/*
 * This file is part of Twig.
 *
 * (c) 2009 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Twig_Node_Expression_Array extends Twig_Node_Expression
{
    public function __construct(array $elements, $lineno)
    {
        parent::__construct($elements, array(), $lineno);
    }

    /**
     * Compiles the node to PHP.
     *
     * @param Twig_Compiler A Twig_Compiler instance
     */
    public function compile($compiler)
    {
        $compiler->raw('array(');
        $first = true;
        foreach ($this->nodes as $name => $node) {
            if (!$first) {
                $compiler->raw(', ');
            }
            $first = false;

            $compiler
                ->repr($name)
                ->raw(' => ')
                ->subcompile($node)
            ;
        }
        $compiler->raw(')');
    }
}
