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
use Twig\Error\SyntaxError;
use Twig\Node\BlockNode;
use Twig\Node\BreakNode;
use Twig\Node\ForNode;
use Twig\Node\MacroNode;
use Twig\Node\Node;
use Twig\Node\WithNode;

final class LoopNodeVisitor implements NodeVisitorInterface
{
    /**
     * @var int
     */
    private $depth = 0;

    /**
     * @var int[]
     */
    private $structureStack = [];

    /**
     * @var Node|null
     */
    private $elseNode;

    public function enterNode(\Twig_NodeInterface $node, Environment $env): Node
    {
        if ($this->isStructureNode($node)) {
            $this->structureStack[] = [$node, $this->depth, $this->elseNode];
            $this->depth = 0;
        } elseif ($node instanceof ForNode) {
            $this->elseNode = $node->hasNode('else') ? $node->getNode('else') : null;
            ++$this->depth;
        } elseif ($node instanceof BreakNode) {
            $target = $node->getAttribute('target');

            if (!\is_int($target)) {
                throw new SyntaxError(sprintf('Break target must be an integer, got "%s".', \is_object($target) ? \get_class($target) : \gettype($target)), $node->getTemplateLine(), $node->getSourceContext());
            }

            if ($target < 1) {
                throw new SyntaxError(sprintf('Break target must be greater than 1, got %s.', $target), $node->getTemplateLine(), $node->getSourceContext());
            }

            if (null !== $this->elseNode) {
                if (1 === $target) {
                    throw new SyntaxError('Cannot break from itself in "else" of "for" .', $node->getTemplateLine(), $node->getSourceContext());
                }

                $node->setTarget(--$target);
            }

            if ($target > $this->depth) {
                if (0 < \count($this->structureStack)) {
                    [$structure] = array_pop($this->structureStack);

                    if ($structure instanceof BlockNode) {
                        throw new SyntaxError('Break tag target outside of loop, did you try to target outside the block?', $node->getTemplateLine(), $node->getSourceContext());
                    }

                    if ($structure instanceof MacroNode) {
                        throw new SyntaxError('Break tag target outside of loop, did you try to target outside the macro?', $node->getTemplateLine(), $node->getSourceContext());
                    }

                    if ($structure instanceof WithNode) {
                        throw new SyntaxError('Break tag target outside of loop, did you try to target outside the "with"?', $node->getTemplateLine(), $node->getSourceContext());
                    }
                }

                throw new SyntaxError('Break tag target outside of loop.', $node->getTemplateLine(), $node->getSourceContext());
            }
        }

        return $node;
    }

    public function leaveNode(\Twig_NodeInterface $node, Environment $env): ?\Twig_NodeInterface
    {
        if ($this->isStructureNode($node)) {
            [, $this->depth, $this->elseNode] = array_pop($this->structureStack);
        } elseif ($node instanceof ForNode) {
            --$this->depth;
        } elseif ($node === $this->elseNode) {
            $this->elseNode = null;
        }

        return $node;
    }

    public function getPriority(): int
    {
        return 0;
    }

    private function isStructureNode(\Twig_NodeInterface $node): bool
    {
        return $node instanceof BlockNode || $node instanceof MacroNode || $node instanceof WithNode;
    }
}
