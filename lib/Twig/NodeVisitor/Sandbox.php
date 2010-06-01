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
 * Twig_NodeVisitor_Sandbox implements sandboxing.
 *
 * @package    twig
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
class Twig_NodeVisitor_Sandbox implements Twig_NodeVisitorInterface
{
    protected $inAModule = false;
    protected $tags;
    protected $filters;

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
        if ($node instanceof Twig_Node_Module) {
            $this->inAModule = true;
            $this->tags = array();
            $this->filters = array();

            return $node;
        } elseif ($this->inAModule) {
            // look for tags
            if ($node->getNodeTag()) {
                $this->tags[] = $node->getNodeTag();
            }

            // look for filters
            if ($node instanceof Twig_Node_Expression_Filter) {
                for ($i = 0; $i < count($node->filters); $i += 2) {
                    $this->filters[] = $node->filters->{$i}['value'];
                }
            }

            // look for simple print statements ({{ article }})
            if ($node instanceof Twig_Node_Print && $node->expr instanceof Twig_Node_Expression_Name) {
                return new Twig_Node_SandboxedPrint($node);
            }
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
        if ($node instanceof Twig_Node_Module) {
            $this->inAModule = false;

            return new Twig_Node_SandboxedModule($node, array_unique($this->filters), array_unique($this->tags));
        }

        return $node;
    }
}
