<?php

class Twig_Node_Expression_Closure extends Twig_Node_Expression {

    public function __construct(array $names, \Twig_Node $body, $lineno)
    {
        parent::__construct(array('body' => $body), array('names' => $names), $lineno);
    }

    public function compile(\Twig_Compiler $compiler)
    {
        $varNames = $this->getAttribute('names');

        $compiler
            ->raw('(function(')
            ->raw(implode(',', array_map(function (\Twig_Token $varName) {
                return '$__'.$varName->getValue().'__';
            }, $varNames)))
            ->raw('){ ')
        ;
        $compiler
            ->raw(implode('', array_map(function (\Twig_Token $varName) {
                return '$context["'.$varName->getValue().'"] = $__'.$varName->getValue().'__;';
            }, $varNames)))
            ->raw(' return ')
            ->subcompile($this->getNode('body'))
            ->raw(';')
            ->raw('})')
        ;
    }

    public function operator(\Twig_Compiler $compiler) {}

}