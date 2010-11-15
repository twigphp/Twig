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
    public function __construct(Twig_NodeInterface $node, Twig_Node_Expression_Constant $filter_name, Twig_NodeInterface $arguments, $lineno, $tag = null)
    {
        parent::__construct(array('node' => $node, 'filter' => $filter_name, 'arguments' => $arguments), array(), $lineno, $tag);
    }

    public function compile($compiler)
    {
        $filterMap = $compiler->getEnvironment()->getFilters();

        $name = $this->getNode('filter')->getAttribute('value');
        $attrs = $this->getNode('arguments');

        if (!isset($filterMap[$name])) {
            throw new Twig_Error_Syntax(sprintf('The filter "%s" does not exist', $name), $this->getLine());
        } else {
            $compiler->raw($filterMap[$name]->compile().($filterMap[$name]->needsEnvironment() ? '($this->env, ' : '('));
        }

        $this->getNode('node')->compile($compiler);

        foreach ($attrs as $node) {
            $compiler
                ->raw(', ')
                ->subcompile($node)
                ;
        }

        $compiler->raw(')');
    }
}
