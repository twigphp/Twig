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
abstract class Twig_BaseNodeVisitor implements \Twig\NodeVisitor\NodeVisitorInterface
{
    final public function enterNode(Twig_NodeInterface $node, \Twig\Environment $env)
    {
        if (!$node instanceof \Twig\Node\Node) {
            throw new \LogicException(sprintf('%s only supports Twig_Node instances.', __CLASS__));
        }

        return $this->doEnterNode($node, $env);
    }

    final public function leaveNode(Twig_NodeInterface $node, \Twig\Environment $env)
    {
        if (!$node instanceof \Twig\Node\Node) {
            throw new \LogicException(sprintf('%s only supports Twig_Node instances.', __CLASS__));
        }

        return $this->doLeaveNode($node, $env);
    }

    /**
     * Called before child nodes are visited.
     *
     * @return \Twig\Node\Node The modified node
     */
    abstract protected function doEnterNode(\Twig\Node\Node $node, \Twig\Environment $env);

    /**
     * Called after child nodes are visited.
     *
     * @return \Twig\Node\Node|false The modified node or false if the node must be removed
     */
    abstract protected function doLeaveNode(\Twig\Node\Node $node, \Twig\Environment $env);
}

class_alias('Twig_BaseNodeVisitor', 'Twig\NodeVisitor\AbstractNodeVisitor', false);
class_exists('Twig_Environment');
class_exists('Twig_Node');
