<?php

/*
 * This file is part of Twig.
 *
 * (c) 2009 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Twig_NodeTraverser is a node traverser.
 *
 * It visits all nodes and their children and call the given visitor for each.
 *
 * @package    twig
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
class Twig_NodeTraverser
{
    protected $env;
    protected $visitors;

    /**
     * Constructor.
     *
     * @param Twig_Environment $env      A Twig_Environment instance
     * @param array            $visitors An array of Twig_NodeVisitorInterface instances
     */
    public function __construct(Twig_Environment $env, array $visitors = array())
    {
        $this->env = $env;
        $this->visitors = array();
        foreach ($visitors as $visitor) {
            $this->addVisitor($visitor);
        }
    }

    /**
     * Adds a visitor.
     *
     * @param Twig_NodeVisitorInterface $visitor A Twig_NodeVisitorInterface instance
     */
    public function addVisitor(Twig_NodeVisitorInterface $visitor)
    {
        $this->visitors[] = $visitor;
    }

    /**
     * Traverses a node and calls the registered visitors.
     *
     * @param Twig_NodeInterface $node A Twig_NodeInterface instance
     */
    public function traverse(Twig_NodeInterface $node = null)
    {
        if (null === $node) {
            return null;
        }

        foreach ($this->visitors as $visitor) {
            $node = $visitor->enterNode($node, $this->env);
        }

        foreach ($node as $k => $n) {
            if (false !== $n = $this->traverse($n)) {
                $node->$k = $n;
            } else {
                unset($node->$k);
            }
        }

        foreach ($this->visitors as $visitor) {
            $node = $visitor->leaveNode($node, $this->env);
        }

        return $node;
    }
}
