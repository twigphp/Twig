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
use Twig\Node\BlockReferenceNode;
use Twig\Node\ConfigNode;
use Twig\Node\MacroNode;
use Twig\Node\ModuleNode;
use Twig\Node\Node;
use Twig\Node\NodeCaptureInterface;
use Twig\Node\Nodes;
use Twig\Node\TextNode;

/**
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @internal
 */
final class CorrectnessNodeVisitor implements NodeVisitorInterface
{
    private ?\WeakMap $rootNodes = null;
    /**
     * Stack of the output-wrapping tags ("if", "for", "set", ...) currently open;
     * the top one is the nearest tag a "block" definition would be nested under.
     *
     * @var list<Node>
     */
    private array $tagStack = [];
    private bool $hasParent = false;
    private int $blockDepth = 0;
    private int $macroDepth = 0;

    public function enterNode(Node $node, Environment $env): Node
    {
        if ($node instanceof ModuleNode) {
            $this->rootNodes = new \WeakMap();
            $this->hasParent = $node->hasNode('parent');
            // reset in case a previous traversal threw before balancing the counters
            $this->tagStack = [];
            $this->blockDepth = 0;
            $this->macroDepth = 0;

            $body = $node->getNode('body')->getNode('0');
            // Parser::subparse() does not wrap the parsed nodes when there is only one,
            // so $body can be a single "real" node instead of a Nodes container; in that
            // case it is the only root node and must not be iterated over its children.
            $rootNodes = $body instanceof Nodes || Node::class === $body::class ? $body : new Nodes([$body]);
            foreach ($rootNodes as $n) {
                // check that this root node of a child template only contains empty output nodes
                if ($this->hasParent && !$this->isEmptyOutputNode($n)) {
                    throw new SyntaxError('A template that extends another one cannot include content outside Twig blocks. Did you forget to put the content inside a {% block %} tag?', $n->getTemplateLine(), $n->getSourceContext());
                }
                $this->rootNodes[$n] = true;
            }

            return $node;
        }

        if ($node instanceof BlockNode) {
            ++$this->blockDepth;
        } elseif ($node instanceof MacroNode) {
            ++$this->macroDepth;
        } elseif ($node->getNodeTag() && !$node instanceof BlockReferenceNode) {
            $this->tagStack[] = $node;
        }

        if ($node instanceof ConfigNode && !isset($this->rootNodes[$node])) {
            // "extends" inside a "block" or a "macro" has always been a hard error; keep it
            if ('extends' === $node->getNodeTag() && $this->blockDepth) {
                throw new SyntaxError('Cannot use "extend" in a block.', $node->getTemplateLine(), $node->getSourceContext());
            }
            if ('extends' === $node->getNodeTag() && $this->macroDepth) {
                throw new SyntaxError('Cannot use "extend" in a macro.', $node->getTemplateLine(), $node->getSourceContext());
            }

            trigger_deprecation('twig/twig', '3.27', 'Using the "%s" tag outside the root of a template is deprecated in %s at line %d.', $node->getNodeTag(), $node->getSourceContext()->getName(), $node->getTemplateLine());
        }

        // A "block" definition nested under an output-wrapping tag is registered globally
        // regardless of that tag, so the nesting is misleading. This only matters at the root
        // of a child template's body: once inside a block or a macro (or in a standalone
        // template), the block is rendered in place and behaves like any other, so there is
        // no restriction.
        if ($this->hasParent && !$this->blockDepth && !$this->macroDepth && $this->tagStack && $node instanceof BlockReferenceNode) {
            $tag = $this->tagStack[array_key_last($this->tagStack)];
            if ($tag instanceof NodeCaptureInterface) {
                // capturing tags (e.g. "set") used to allow this, so only deprecate it
                trigger_deprecation('twig/twig', '3.14', \sprintf('Having a "block" tag under a "%s" tag (line %d) is deprecated in %s at line %d.', $tag->getNodeTag(), $tag->getTemplateLine(), $node->getSourceContext()->getName(), $node->getTemplateLine()));
            } else {
                throw new SyntaxError(\sprintf('A "block" tag cannot be under a "%s" tag (line %d).', $tag->getNodeTag(), $tag->getTemplateLine()), $node->getTemplateLine(), $node->getSourceContext());
            }
        }

        return $node;
    }

    public function leaveNode(Node $node, Environment $env): Node
    {
        if ($node instanceof ModuleNode) {
            $this->rootNodes = null;
            $this->hasParent = false;

            return $node;
        }
        if ($node instanceof BlockNode) {
            --$this->blockDepth;
        } elseif ($node instanceof MacroNode) {
            --$this->macroDepth;
        } elseif ($node->getNodeTag() && !$node instanceof BlockReferenceNode) {
            array_pop($this->tagStack);
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
