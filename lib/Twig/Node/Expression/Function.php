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

    public function compile(Twig_Compiler $compiler)
    {
        $function = $compiler->getEnvironment()->getFunction($this->getNode('name')->getAttribute('name'));
        if (false === $function) {
            throw new Twig_Error_Syntax(sprintf('The function "%s" does not exist', $this->getNode('name')->getAttribute('name')), $this->getLine());
        }

        $compiler
            ->raw($function->compile().'(')
            ->raw($function->needsEnvironment() ? '$this->env, ' : '')
            ->raw($function->needsContext() ? '$context, ' : '')
        ;

        $first = true;
        foreach ($this->getNode('arguments') as $node) {
            if (!$first) {
                $compiler->raw(', ');
            } else {
                $first = false;
            }
            $compiler->subcompile($node);
        }

        $compiler->raw(')');
    }
}
