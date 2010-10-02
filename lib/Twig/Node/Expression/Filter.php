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
    public function __construct(Twig_NodeInterface $node, Twig_NodeInterface $filters, $lineno, $tag = null)
    {
        parent::__construct(array('node' => $node, 'filters' => $filters), array(), $lineno, $tag);
    }

    public function compile($compiler)
    {
        $filterMap = $compiler->getEnvironment()->getFilters();

        $postponed = array();
        for ($i = count($this->getNode('filters')) - 1; $i >= 0; $i -= 2) {
            $name = $this->getNode('filters')->getNode($i - 1)->getAttribute('value');
            $attrs = $this->getNode('filters')->getNode($i);
            if (!isset($filterMap[$name])) {
                throw new Twig_SyntaxError(sprintf('The filter "%s" does not exist', $name), $this->getLine());
            } else {
                $compiler->raw($filterMap[$name]->compile().($filterMap[$name]->needsEnvironment() ? '($this->env, ' : '('));
            }
            $postponed[] = $attrs;
        }

        $this->getNode('node')->compile($compiler);

        foreach (array_reverse($postponed) as $attributes) {
            foreach ($attributes as $node) {
                $compiler
                    ->raw(', ')
                    ->subcompile($node)
                ;
            }
            $compiler->raw(')');
        }
    }

    public function prependFilter(Twig_Node_Expression_Constant $name, Twig_Node $end)
    {
        $filters = array($name, $end);
        foreach ($this->getNode('filters') as $node) {
            $filters[] = $node;
        }

        $this->setNode('filters', new Twig_Node($filters, array(), $this->getNode('filters')->getLine()));
    }

    public function appendFilter(Twig_Node_Expression_Constant $name, Twig_Node $end)
    {
        $filters = array();
        foreach ($this->getNode('filters') as $node) {
            $filters[] = $node;
        }

        $filters[] = $name;
        $filters[] = $end;

        $this->setNode('filters', new Twig_Node($filters, array(), $this->getNode('filters')->getLine()));
    }

    public function appendFilters(Twig_NodeInterface $filters)
    {
        for ($i = 0; $i < count($filters); $i += 2) {
            $this->appendFilter($filters->getNode($i), $filters->getNode($i + 1));
        }
    }

    public function hasFilter($name)
    {
        for ($i = 0; $i < count($this->getNode('filters')); $i += 2) {
            if ($name == $this->getNode('filters')->getNode($i)->getAttribute('value')) {
                return true;
            }
        }

        return false;
    }
}
