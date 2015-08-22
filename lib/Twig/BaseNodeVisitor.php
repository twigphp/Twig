<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Twig_BaseNodeVisitor can be used to make node visitors compatible with Twig 1.x and 2.x.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
abstract class Twig_BaseNodeVisitor implements Twig_NodeVisitorInterface
{
    /**
     * {@inheritdoc}
     */
    final public function enterNode(Twig_Node $node, Twig_Environment $env)
    {
        return $this->doEnterNode($node, $env);
    }

    /**
     * {@inheritdoc}
     */
    final public function leaveNode(Twig_Node $node, Twig_Environment $env)
    {
        return $this->doLeaveNode($node, $env);
    }

    /**
     * Called before child nodes are visited.
     *
     * @param Twig_Node        $node The node to visit
     * @param Twig_Environment $env  The Twig environment instance
     *
     * @return Twig_Node The modified node
     */
    abstract protected function doEnterNode(Twig_Node $node, Twig_Environment $env);

    /**
     * Called after child nodes are visited.
     *
     * @param Twig_Node        $node The node to visit
     * @param Twig_Environment $env  The Twig environment instance
     *
     * @return Twig_Node|false The modified node or false if the node must be removed
     */
    abstract protected function doLeaveNode(Twig_Node $node, Twig_Environment $env);
}
