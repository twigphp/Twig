<?php

/*
 * This file is part of Twig.
 *
 * (c) 2012 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Twig_Node_Expression_GetProperty extends Twig_Node_Expression
{
    public function __construct(Twig_Node_Expression $node, $name, $lineno)
    {
        parent::__construct(array('node' => $node), array('name' => $name, 'safe' => false), $lineno);

        if ($node instanceof Twig_Node_Expression_Name) {
            $node->setAttribute('always_defined', true);
        }
    }

    public function compile(Twig_Compiler $compiler)
    {
        $compiler
            ->subcompile($this->getNode('node'))
            ->raw('->')
            ->raw($this->getAttribute('name'))
        ;
    }
}
