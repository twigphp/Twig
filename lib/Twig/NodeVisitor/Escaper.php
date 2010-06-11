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
 * @version    SVN: $Id$
 */
class Twig_NodeVisitor_Escaper implements Twig_NodeVisitorInterface
{
    protected $statusStack = array();
    protected $blocks = array();

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
            $this->statusStack[] = $node['value'];
        } elseif ($node instanceof Twig_Node_Print) {
            return $this->escapeNode($node, $env, $this->needEscaping($env));
        } elseif ($node instanceof Twig_Node_Block) {
            $this->statusStack[] = isset($this->blocks[$node['name']]) ? $this->blocks[$node['name']] : $this->needEscaping($env);
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
        if ($node instanceof Twig_Node_AutoEscape || $node instanceof Twig_Node_Block) {
            array_pop($this->statusStack);
        } elseif ($node instanceof Twig_Node_BlockReference) {
            $this->blocks[$node['name']] = $this->needEscaping($env);
        }

        return $node;
    }

    protected function escapeNode(Twig_NodeInterface $node, Twig_Environment $env, $type)
    {
        if (false === $type) {
            return $node;
        }

        $expression = $node instanceof Twig_Node_Print ? $node->expr : $node;

        if ($expression instanceof Twig_Node_Expression_Filter) {
            // don't escape if the primary node of the filter is not a variable
            if (!$expression->node instanceof Twig_Node_Expression_GetAttr && !$expression->node instanceof Twig_Node_Expression_Name) {
                return $node;
            }

            // don't escape if there is already an "escaper" in the filter chain
            $filterMap = $env->getFilters();
            for ($i = 0; $i < count($expression->filters); $i += 2) {
                $name = $expression->filters->{$i}['value'];
                if (isset($filterMap[$name]) && $filterMap[$name]->isEscaper()) {
                    return $node;
                }
            }
        } elseif (!$expression instanceof Twig_Node_Expression_GetAttr && !$expression instanceof Twig_Node_Expression_Name) {
            // don't escape if the node is not a variable
            return $node;
        }

        // escape
        if ($expression instanceof Twig_Node_Expression_Filter) {
            // escape all variables in filters arguments
            for ($i = 0; $i < count($expression->filters); $i += 2) {
                foreach ($expression->filters->{$i + 1} as $j => $n) {
                    $expression->filters->{$i + 1}->{$j} = $this->escapeNode($n, $env, $type);
                }
            }

            $filter = $this->getEscaperFilter($type, $expression->getLine());
            $expression->prependFilter($filter[0], $filter[1]);

            return $node;
        } elseif ($node instanceof Twig_Node_Print) {
            return new Twig_Node_Print(
                new Twig_Node_Expression_Filter($expression, new Twig_Node($this->getEscaperFilter($type, $node->getLine())), $node->getLine())
                , $node->getLine()
            );
        } else {
            return new Twig_Node_Expression_Filter($node, new Twig_Node($this->getEscaperFilter($type, $node->getLine())), $node->getLine());
        }
    }

    protected function needEscaping(Twig_Environment $env)
    {
        if (count($this->statusStack)) {
            return $this->statusStack[count($this->statusStack) - 1];
        } else {
            return $env->hasExtension('escaper') ? $env->getExtension('escaper')->isGlobal() : false;
        }
    }

    protected function getEscaperFilter($type, $line)
    {
        return array(new Twig_Node_Expression_Constant('escape', $line), new Twig_Node(array(new Twig_Node_Expression_Constant((string) $type, $line))));
    }
}
