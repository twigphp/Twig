<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Twig_Node_Expression_EmptyCoalesce extends \Twig_Node_Expression
{

    public function __construct(Twig_Node $left, Twig_Node $right, $lineno)
    {
        $left->setAttribute('ignore_strict_check', true);
        $left->setAttribute('is_defined_test', false);
        $right->setAttribute('ignore_strict_check', true);
        $right->setAttribute('is_defined_test', false);
        parent::__construct(
            ['left' => $left, 'right' => $right],
            ['ignore_strict_check' => true, 'is_defined_test' => false],
            $lineno
        );
    }

    public function compile(\Twig_Compiler $compiler)
    {
            $compiler
                ->raw('((empty(')
                ->subcompile($this->getNode('left'))
                ->raw(') ? null : ')
                ->subcompile($this->getNode('left'))
                ->raw(') ?? (empty(')
                ->subcompile($this->getNode('right'))
                ->raw(') ? null : ')
                ->subcompile($this->getNode('right'))
                ->raw('))')
            ;
    }
}

class_alias('Twig_Node_Expression_EmptyCoalesce', 'Twig\Node\Expression\EmptyCoalesceExpression', false);
