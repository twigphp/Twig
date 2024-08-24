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
use Twig\Node\BlockReferenceNode;
use Twig\Node\ConfigNode;
use Twig\Node\ModuleNode;
use Twig\Node\Node;
use Twig\Node\NodeCaptureInterface;
use Twig\Node\TextNode;

/**
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @internal
 */
final class CorrectnessNodeVisitor implements NodeVisitorInterface
{
    private ?\SplObjectStorage $rootNodes = null;
    // in a tag node that does not support "block" nodes (all of them except "block")
    private ?Node $currentTagNode = null;
    private bool $hasParent = false;
    private ?\SplObjectStorage $blockNodes = null;
    private int $currentBlockNodeLevel = 0;

    public function enterNode(Node $node, Environment $env): Node
    {
        if ($node instanceof ModuleNode) {
            $this->rootNodes = new \SplObjectStorage();
            $this->hasParent = $node->hasNode('parent');

            // allows to identify when we enter/leave the block nodes
            $this->blockNodes = new \SplObjectStorage();
            foreach ($node->getNode('blocks') as $n) {
                $this->blockNodes->attach($n);
            }

            $body = $node->getNode('body')->getNode('0');
            // see Parser::subparse() which does not wrap the parsed Nodes if there is only one node
            foreach (count($body) ? $body : new Node([$body]) as $k => $n) {
                // check that this root node of a child template only contains empty output nodes
                if ($this->hasParent && !$this->isEmptyOutputNode($n)) {
                    throw new SyntaxError('A template that extends another one cannot include content outside Twig blocks. Did you forget to put the content inside a {% block %} tag?', $n->getTemplateLine(), $n->getSourceContext());
                }
                $this->rootNodes->attach($n);
            }

            return $node;
        }

        if ($this->blockNodes->contains($node)) {
            ++$this->currentBlockNodeLevel;
        }

        if ($this->hasParent && $node->getNodeTag() && !$node instanceof BlockReferenceNode) {
            $this->currentTagNode = $node;
        }

        if ($node instanceof ConfigNode && !$this->rootNodes->contains($node)) {
            throw new SyntaxError(sprintf('The "%s" tag must always be at the root of the body of a template.', $node->getNodeTag()), $node->getTemplateLine(), $node->getSourceContext());
        }

        if ($this->currentTagNode && $node instanceof BlockReferenceNode) {
            if ($this->currentTagNode instanceof NodeCaptureInterface || count($this->blockNodes) > 1) {
                trigger_deprecation('twig/twig', '3.14', \sprintf('Having a "block" tag under a "%s" tag (line %d) is deprecated in %s at line %d.', $this->currentTagNode->getNodeTag(), $this->currentTagNode->getTemplateLine(), $node->getSourceContext()->getName(), $node->getTemplateLine()));
            } else {
                throw new SyntaxError(\sprintf('A "block" tag cannot be under a "%s" tag (line %d).', $this->currentTagNode->getNodeTag(), $this->currentTagNode->getTemplateLine()), $node->getTemplateLine(), $node->getSourceContext());
            }
        }

        return $node;
    }

    public function leaveNode(Node $node, Environment $env): Node
    {
        if ($node instanceof ModuleNode) {
            $this->rootNodes = null;
            $this->hasParent = false;
            $this->blockNodes = null;
            $this->currentBlockNodeLevel = 0;
        }
        if ($this->hasParent && $node->getNodeTag() && !$node instanceof BlockReferenceNode) {
            $this->currentTagNode = null;
        }
        if ($this->hasParent && $this->blockNodes->contains($node)) {
            --$this->currentBlockNodeLevel;
        }

        return $node;
    }

    public function getPriority(): int
    {
        return -255;
    }

    /**
     * Returns true if the node never outputs anything or if the output is empty.
     */
    private function isEmptyOutputNode(Node $node): bool
    {
        if ($node instanceof NodeCaptureInterface) {
            // a "block" tag in such a node will serve as a block definition AND be displayed in place as well
            return true;
        }

        // Can the text be considered "empty" (only whitespace)?
        if ($node instanceof TextNode) {
            return $node->isBlank();
        }

        foreach ($node as $n) {
            if (!$this->isEmptyOutputNode($n)) {
                return false;
            }
        }

        return true;
    }
}
