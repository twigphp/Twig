<?php

/*
 * This file is part of Twig.
 *
 * (c) 2010 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Twig_Node_Expression_Function extends Twig_Node_Expression
{
    public function __construct(Twig_Node_Expression_Name $name, Twig_NodeInterface $arguments, $lineno)
    {
        parent::__construct(array('name' => $name, 'arguments' => $arguments), array(), $lineno);
    }

    public function compile($compiler)
    {
        // functions must be prefixed with fn_
        $this->getNode('name')->setAttribute('name', 'fn_'.$this->getNode('name')->getAttribute('name'));

        $compiler
            ->raw('$this->callFunction($context, ')
            ->subcompile($this->getNode('name'))
            ->raw(', array(')
        ;

        foreach ($this->getNode('arguments') as $node) {
            $compiler
                ->subcompile($node)
                ->raw(', ')
            ;
        }

        $compiler->raw('))');
    }
}
