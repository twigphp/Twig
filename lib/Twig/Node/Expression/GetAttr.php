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
class Twig_Node_Expression_GetAttr extends Twig_Node_Expression
{
    const TYPE_ANY = 'any';
    const TYPE_ARRAY = 'array';
    const TYPE_METHOD = 'method';

    public function __construct(Twig_Node_Expression $node, Twig_Node_Expression $attribute, Twig_NodeInterface $arguments, $type, $lineno)
    {
        parent::__construct(array('node' => $node, 'attribute' => $attribute, 'arguments' => $arguments), array('type' => $type), $lineno);
    }

    public function compile($compiler)
    {
        $compiler
            ->raw('$this->getAttribute(')
            ->subcompile($this->node)
            ->raw(', ')
            ->subcompile($this->attribute)
            ->raw(', array(')
        ;

        foreach ($this->arguments as $node) {
            $compiler
                ->subcompile($node)
                ->raw(', ')
            ;
        }

        $compiler
            ->raw('), ')
            ->repr($this['type'])
            ->raw(')');
    }
}
