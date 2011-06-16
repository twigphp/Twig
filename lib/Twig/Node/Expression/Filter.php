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
class Twig_Node_Expression_Filter extends Twig_Node_Expression
{
    public function __construct(Twig_NodeInterface $node, Twig_Node_Expression_Constant $filterName, Twig_NodeInterface $arguments, $lineno, $tag = null)
    {
        parent::__construct(array('node' => $node, 'filter' => $filterName, 'arguments' => $arguments), array(), $lineno, $tag);
    }

    public function compile(Twig_Compiler $compiler)
    {
        $name = $this->getNode('filter')->getAttribute('value');
        if (false === $filter = $compiler->getEnvironment()->getFilter($name)) {
            throw new Twig_Error_Syntax(sprintf('The filter "%s" does not exist', $name), $this->getLine());
        }

        $node = $this->getNode('node');

        // The default filter is intercepted when the filtered value
        // is a name (like obj) or an attribute (like obj.attr)
        // In such a case, it's compiled to {{ obj is defined ? obj|default('bar') : 'bar' }}
        if ('default' === $name && ($node instanceof Twig_Node_Expression_Name || $node instanceof Twig_Node_Expression_GetAttr)) {
            $compiler
                ->raw('((')
                ->subcompile(new Twig_Node_Expression_Test($node, 'defined', new Twig_Node(), $this->getLine()))
                ->raw(') ? (')
            ;

            $this->compileFilter($compiler, $filter);

            $compiler->raw(') : (');

            if ($this->getNode('arguments')->hasNode(0)) {
                $compiler->subcompile($this->getNode('arguments')->getNode(0));
            } else {
                $compiler->string('');
            }

            $compiler->raw('))');
        } else {
            $this->compileFilter($compiler, $filter);
        }
    }

    protected function compileFilter(Twig_Compiler $compiler, Twig_FilterInterface $filter)
    {
        $compiler
            ->raw($filter->compile().'(')
            ->raw($filter->needsEnvironment() ? '$this->env, ' : '')
            ->raw($filter->needsContext() ? '$context, ' : '')
            ->subcompile($this->getNode('node'))
        ;

        foreach ($this->getNode('arguments') as $node) {
            $compiler
                ->raw(', ')
                ->subcompile($node)
            ;
        }

        $compiler->raw(')');
    }
}
