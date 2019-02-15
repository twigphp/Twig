<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 * (c) Armin Ronacher
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
abstract class Twig_Node_Expression_Binary extends \Twig\Node\Expression\AbstractExpression
{
    public function __construct(\Twig\Node\Node $left, \Twig\Node\Node $right, $lineno)
    {
        parent::__construct(['left' => $left, 'right' => $right], [], $lineno);
    }

    public function compile(\Twig\Compiler $compiler)
    {
        $compiler
            ->raw('(')
            ->subcompile($this->getNode('left'))
            ->raw(' ')
        ;
        $this->operator($compiler);
        $compiler
            ->raw(' ')
            ->subcompile($this->getNode('right'))
            ->raw(')')
        ;
    }

    abstract public function operator(\Twig\Compiler $compiler);
}

class_alias('Twig_Node_Expression_Binary', 'Twig\Node\Expression\Binary\AbstractBinary', false);
