<?php

/*
 * This file is part of Twig.
 *
 * (c) 2012 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Twig_Node_Expression_MethodCall extends Twig_Node_Expression
{
    public function __construct(Twig_Node_Expression $node, $method, Twig_NodeInterface $arguments, $lineno)
    {
        parent::__construct(array('node' => $node, 'arguments' => $arguments), array('method' => $method), $lineno);
    }

    public function compile(Twig_Compiler $compiler)
    {
        $compiler
            ->subcompile($this->getNode('node'))
            ->raw('->')
            ->raw($this->getAttribute('method'))
            ->raw('(')
        ;
        $nodes = $this->getNode('arguments');
        for ($i = 0, $max = count($nodes); $i < $max; $i++) {
            $compiler->subcompile($nodes->getNode($i));

            if ($i < $max - 1) {
                $compiler->raw(', ');
            }
        }
        $compiler->raw(')');
    }
}
