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
 * Twig_NodeVisitor_Escaper implements output escaping.
 *
 * @package    twig
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 */
class Twig_NodeVisitor_Escaper implements Twig_NodeVisitorInterface
{
    protected $statusStack = array();
    protected $blocks = array();

    protected $safeAnalysis;
    protected $traverser;

    function __construct()
    {
        $this->safeAnalysis = new Twig_NodeVisitor_SafeAnalysis();
    }

    /**
     * Called before child nodes are visited.
     *
     * @param Twig_NodeInterface $node The node to visit
     * @param Twig_Environment   $env  The Twig environment instance
     *
     * @param Twig_NodeInterface The modified node
     */
    public function enterNode(Twig_NodeInterface $node, Twig_Environment $env)
    {
        if ($node instanceof Twig_Node_AutoEscape) {
            $this->statusStack[] = $node->getAttribute('value');
        } elseif ($node instanceof Twig_Node_Block) {
            $this->statusStack[] = isset($this->blocks[$node->getAttribute('name')]) ? $this->blocks[$node->getAttribute('name')] : $this->needEscaping($env);
        }

        return $node;
    }

    /**
     * Called after child nodes are visited.
     *
     * @param Twig_NodeInterface $node The node to visit
     * @param Twig_Environment   $env  The Twig environment instance
     *
     * @param Twig_NodeInterface The modified node
     */
    public function leaveNode(Twig_NodeInterface $node, Twig_Environment $env)
    {
        if ($node instanceof Twig_Node_Expression_Filter) {
            return $this->preEscapeFilterNode($node, $env);
        } elseif ($node instanceof Twig_Node_Print) {
            return $this->escapePrintNode($node, $env, $this->needEscaping($env));
        }

        if ($node instanceof Twig_Node_AutoEscape || $node instanceof Twig_Node_Block) {
            array_pop($this->statusStack);
        } elseif ($node instanceof Twig_Node_BlockReference) {
            $this->blocks[$node->getAttribute('name')] = $this->needEscaping($env);
        }

        return $node;
    }

    protected function escapePrintNode(Twig_Node_Print $node, Twig_Environment $env, $type)
    {
        if (false === $type) {
            return $node;
        }

        $expression = $node->getNode('expr');

        if ($this->isSafeFor($type, $expression, $env)) {
            return $node;
        }

        $class = get_class($node);

        return new $class(
            $this->getEscaperFilter($type, $expression),
            $node->getLine()
        );
    }

    protected function preEscapeFilterNode(Twig_Node_Expression_Filter $filter, Twig_Environment $env)
    {
        $filterMap = $env->getFilters();
        $name = $filter->getNode('filter')->getAttribute('value');

        if (isset($filterMap[$name])) {
            $type = $filterMap[$name]->getPreEscape();
            if (null === $type) {
                return $filter;
            }

            $node = $filter->getNode('node');
            if ($this->isSafeFor($type, $node, $env)) {
                return $filter;
            }

            $filter->setNode('node', $this->getEscaperFilter($type, $node));

            return $filter;
        }

        return $filter;
    }

    protected function isSafeFor($type, Twig_NodeInterface $expression, $env)
    {
        $safe = $this->safeAnalysis->getSafe($expression);

        if (null === $safe) {
            if (null === $this->traverser) {
                $this->traverser = new Twig_NodeTraverser($env, array($this->safeAnalysis));
            }
            $this->traverser->traverse($expression);
            $safe = $this->safeAnalysis->getSafe($expression);
        }

        return in_array($type, $safe) || in_array('all', $safe);
    }

    protected function needEscaping(Twig_Environment $env)
    {
        if (count($this->statusStack)) {
            return $this->statusStack[count($this->statusStack) - 1];
        }

        if ($env->hasExtension('escaper') && $env->getExtension('escaper')->isGlobal()) {
            return 'html';
        }

        return false;
    }

    protected function getEscaperFilter($type, Twig_NodeInterface $node)
    {
        $line = $node->getLine();
        $name = new Twig_Node_Expression_Constant('escape', $line);
        $args = new Twig_Node(array(new Twig_Node_Expression_Constant((string) $type, $line)));
        return new Twig_Node_Expression_Filter($node, $name, $args, $line);
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        return 0;
    }
}
