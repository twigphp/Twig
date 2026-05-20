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
use Twig\Node\CheckSecurityCallNode;
use Twig\Node\CheckSecurityNode;
use Twig\Node\CheckToStringNode;
use Twig\Node\CoercesChildrenToStringInterface;
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Expression\Binary\RangeBinary;
use Twig\Node\Expression\FilterExpression;
use Twig\Node\Expression\FunctionExpression;
use Twig\Node\Expression\GetAttrExpression;
use Twig\Node\Expression\OperatorEscapeInterface;
use Twig\Node\Expression\Unary\SpreadUnary;
use Twig\Node\Expression\Variable\ContextVariable;
use Twig\Node\ModuleNode;
use Twig\Node\Node;
use Twig\Node\Nodes;

/**
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @internal
 */
final class SandboxNodeVisitor implements NodeVisitorInterface
{
    private $inAModule = false;
    /** @var array<string, int> */
    private $tags;
    /** @var array<string, int> */
    private $filters;
    /** @var array<string, int> */
    private $functions;

    public function enterNode(Node $node, Environment $env): Node
    {
        if ($node instanceof ModuleNode) {
            $this->inAModule = true;
            $this->tags = [];
            $this->filters = [];
            $this->functions = [];
        } elseif ($this->inAModule) {
            // look for tags
            if ($node->getNodeTag() && !isset($this->tags[$node->getNodeTag()])) {
                $this->tags[$node->getNodeTag()] = $node->getTemplateLine();
            }

            // look for filters
            if ($node instanceof FilterExpression && !isset($this->filters[$node->getAttribute('name')])) {
                $this->filters[$node->getAttribute('name')] = $node->getTemplateLine();
            }

            // look for functions
            if ($node instanceof FunctionExpression && !isset($this->functions[$node->getAttribute('name')])) {
                $this->functions[$node->getAttribute('name')] = $node->getTemplateLine();
            }

            // the .. operator is equivalent to the range() function
            if ($node instanceof RangeBinary && !isset($this->functions['range'])) {
                $this->functions['range'] = $node->getTemplateLine();
            }
        }

        // wrap children that the node itself will string-coerce at runtime;
        // applies to ModuleNode (`parent` slot for {% extends %}) too
        if ($this->inAModule && $node instanceof CoercesChildrenToStringInterface) {
            foreach ($node->getStringCoercedChildNames() as $childName) {
                $this->wrapNode($node, $childName);
            }
        }

        return $node;
    }

    public function leaveNode(Node $node, Environment $env): ?Node
    {
        if ($node instanceof ModuleNode) {
            $this->inAModule = false;

            $node->setNode('constructor_end', new Nodes([new CheckSecurityCallNode(), $node->getNode('constructor_end')]));
            $node->setNode('class_end', new Nodes([new CheckSecurityNode($this->filters, $this->tags, $this->functions), $node->getNode('class_end')]));
        }

        return $node;
    }

    private function wrapNode(Node $node, string $name): void
    {
        $expr = $node->getNode($name);
        // `_self` is internal: it compiles to `$this->getTemplateName()` and is always a string
        if ($expr instanceof ContextVariable && '_self' === $expr->getAttribute('name')) {
            return;
        }
        if (($expr instanceof ContextVariable || $expr instanceof GetAttrExpression) && !$expr->isGenerator()) {
            $node->setNode($name, new CheckToStringNode($expr));
        } elseif ($expr instanceof SpreadUnary) {
            $expr->setNode('node', new CheckToStringNode($expr->getNode('node'), true));
        } elseif ($expr instanceof ArrayExpression || $expr instanceof Nodes) {
            foreach ($expr as $name => $_) {
                $this->wrapNode($expr, $name);
            }
        } elseif ($expr instanceof OperatorEscapeInterface) {
            foreach ($expr->getOperandNamesToEscape() as $operandName) {
                $this->wrapNode($expr, $operandName);
            }
        } elseif ($expr instanceof FilterExpression || $expr instanceof FunctionExpression) {
            $node->setNode($name, new CheckToStringNode($expr));
        }
    }

    public function getPriority(): int
    {
        return 0;
    }
}
