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
class Twig_Node_Expression_Compare extends Twig_Node_Expression
{
    public function __construct(Twig_Node_Expression $expr, Twig_NodeInterface $ops, $lineno)
    {
        parent::__construct(array('expr' => $expr, 'ops' => $ops), array(), $lineno);
    }

    public function compile($compiler)
    {
        if ('in' === $this->getNode('ops')->getNode('0')->getAttribute('value')) {
            return $this->compileIn($compiler);
        }

        $this->getNode('expr')->compile($compiler);

        $nbOps = count($this->getNode('ops'));
        for ($i = 0; $i < $nbOps; $i += 2) {
            if ($i > 0) {
                $compiler->raw(' && ($tmp'.($i / 2));
            }

            $compiler->raw(' '.$this->getNode('ops')->getNode($i)->getAttribute('value').' ');

            if ($i != $nbOps - 2) {
                $compiler
                    ->raw('($tmp'.(($i / 2) + 1).' = ')
                    ->subcompile($this->getNode('ops')->getNode($i + 1))
                    ->raw(')')
                ;
            } else {
                $compiler->subcompile($this->getNode('ops')->getNode($i + 1));
            }
        }

        for ($j = 1; $j < $i / 2; $j++) {
            $compiler->raw(')');
        }
    }

    protected function compileIn($compiler)
    {
        $compiler
            ->raw('twig_in_filter(')
            ->subcompile($this->getNode('expr'))
            ->raw(', ')
            ->subcompile($this->getNode('ops')->getNode(1))
            ->raw(')')
        ;
    }
}
