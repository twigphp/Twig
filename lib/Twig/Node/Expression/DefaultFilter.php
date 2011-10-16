<?php

/*
 * This file is part of Twig.
 *
 * (c) 2011 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Twig_Node_Expression_DefaultFilter extends Twig_Node_Expression
{
    public function __construct(Twig_Node_Expression_Filter $node)
    {
        if (!self::isDefaultFilter($node)) {
            throw new Twig_Error('The default filter node cannot be created from the given node.', $node->getLine());
        }

        $test = new Twig_Node_Expression_Test(clone $node->getNode('node'), 'defined', new Twig_Node(), $node->getLine());
        $default = count($node->getNode('arguments')) ? $node->getNode('arguments')->getNode(0) : new Twig_Node_Expression_Constant('', $node->getLine());

        $node = new Twig_Node_Expression_Conditional($test, $node, $default, $node->getLine());

        parent::__construct(array('node' => $node), array(), $node->getLine());
    }

    public function compile(Twig_Compiler $compiler)
    {
        $compiler->subcompile($this->getNode('node'));
    }

    /**
     * Checks whether a node is a default filter that needs to be wrapped with Twig_Node_Expression_DefaultFilter.
     *
     * The default filter needs to be wrapped with an instance of Twig_Node_Expression_DefaultFilter
     * when the filtered value is a name (like obj) or an attribute (like obj.attr).
     *
     * In such a case, it's compiled to {{ obj is defined ? obj|default('bar') : 'bar' }}
     *
     * @param Twig_NodeInterface $node A Twig_NodeInterface instance
     *
     * @return Boolean true if the node must be wrapped with a Twig_Node_Expression_DefaultFilter, false otherwise
     */
    static public function isDefaultFilter(Twig_NodeInterface $node)
    {
        return $node instanceof Twig_Node_Expression_Filter && 'default' === $node->getNode('filter')->getAttribute('value') && ($node->getNode('node') instanceof Twig_Node_Expression_Name || $node->getNode('node') instanceof Twig_Node_Expression_GetAttr);
    }
}
