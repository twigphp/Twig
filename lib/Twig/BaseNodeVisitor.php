<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Twig\Environment;
use Twig\Node\Node;
use Twig\NodeVisitor\NodeVisitorInterface;

/**
 * Twig_BaseNodeVisitor can be used to make node visitors compatible with Twig 1.x and 2.x.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
abstract class Twig_BaseNodeVisitor implements NodeVisitorInterface
{
    final public function enterNode(Twig_NodeInterface $node, Environment $env)
    {
        if (!$node instanceof Node) {
            throw new \LogicException(sprintf('%s only supports Twig_Node instances.', __CLASS__));
        }

        return $this->doEnterNode($node, $env);
    }

    final public function leaveNode(Twig_NodeInterface $node, Environment $env)
    {
        if (!$node instanceof Node) {
            throw new \LogicException(sprintf('%s only supports Twig_Node instances.', __CLASS__));
        }

        return $this->doLeaveNode($node, $env);
    }

    /**
     * Called before child nodes are visited.
     *
     * @return Node The modified node
     */
    abstract protected function doEnterNode(Node $node, Environment $env);

    /**
     * Called after child nodes are visited.
     *
     * @return Node|false The modified node or false if the node must be removed
     */
    abstract protected function doLeaveNode(Node $node, Environment $env);
}

class_alias('Twig_BaseNodeVisitor', 'Twig\NodeVisitor\AbstractNodeVisitor', false);
class_exists('Twig_Environment');
class_exists('Twig_Node');
