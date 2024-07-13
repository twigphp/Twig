<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\NodeVisitor;

use Twig\Environment;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\GetAttrExpression;
use Twig\Node\Expression\NameExpression;
use Twig\Node\ForNode;
use Twig\Node\Node;
use Twig\Node\PrintNode;

/**
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @internal
 */
final class ForNodeVisitor implements NodeVisitorInterface
{
    private int $loops = 0;

    public function enterNode(Node $node, Environment $env): Node
    {
        if ($node instanceof ForNode) {
            ++$this->loops;

            return $node;
        } elseif (!$this->loops) {
            // we are outside a loop
            return $node;
        }

        if (!$node instanceof PrintNode) {
            return $node;
        }

        // We look for exactly {{ loop.recurse(...) }}
        $exprNode = $node->getNode('expr');
        if (
            $exprNode instanceof GetAttrExpression
            && $exprNode->getNode('node') instanceof NameExpression
            && 'loop' === $exprNode->getNode('node')->getAttribute('name')
            && $exprNode->getNode('attribute') instanceof ConstantExpression
            && 'recurse' === $exprNode->getNode('attribute')->getAttribute('value')
        ) {
            $exprNode->setAttribute('is_generator', true);
        }

        return $node;
    }

    public function leaveNode(Node $node, Environment $env): ?Node
    {
        if ($node instanceof ForNode) {
            --$this->loops;
        }

        return $node;
    }

    public function getPriority(): int
    {
        return 0;
    }
}
