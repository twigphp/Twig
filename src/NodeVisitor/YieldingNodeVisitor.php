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
use Twig\Node\Node;
use Twig\Node\PrintNode;
use Twig\Node\TextNode;
use Twig\Node\YieldExpressionNode;
use Twig\Node\YieldTextNode;

/**
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @internal
 */
final class YieldingNodeVisitor implements NodeVisitorInterface
{
    private $yielding;

    public function __construct(bool $yielding)
    {
        $this->yielding = $yielding;
    }

    public function enterNode(Node $node, Environment $env): Node
    {
        if ($node instanceof YieldExpressionNode) {
            if ($this->yielding) {
                return $node;
            }

            return new PrintNode($node->getNode('expr'), $node->getTemplateLine(), $node->getNodeTag());
        }
        if ($node instanceof YieldTextNode) {
            if ($this->yielding) {
                return $node;
            }

            return new TextNode($node->getAttribute('data'), $node->getTemplateLine());
        }

        if ($node instanceof PrintNode) {
            // FIXME: deprecation
            if (!$this->yielding) {
                return $node;
            }

            return new YieldExpressionNode($node->getNode('expr'), $node->getTemplateLine(), $node->getNodeTag());
        }
        if ($node instanceof TextNode) {
            // FIXME: deprecation
            if (!$this->yielding) {
                return $node;
            }

            return new YieldTextNode($node->getAttribute('data'), $node->getTemplateLine());
        }

        return $node;
    }

    public function leaveNode(Node $node, Environment $env): ?Node
    {
        return $node;
    }

    public function getPriority(): int
    {
        return 255;
    }
}
