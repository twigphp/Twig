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
use Twig\Extension\EscaperExtension;
use Twig\Node\AutoEscapeNode;
use Twig\Node\BlockNode;
use Twig\Node\BlockReferenceNode;
use Twig\Node\DoNode;
use Twig\Node\Expression\ConditionalExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\FilterExpression;
use Twig\Node\Expression\InlinePrint;
use Twig\Node\ImportNode;
use Twig\Node\ModuleNode;
use Twig\Node\Node;
use Twig\Node\PrintNode;
use Twig\NodeTraverser;

/**
 * @author Fabien Potencier <fabien@symfony.com>
 */
final class EscaperNodeVisitor implements NodeVisitorInterface
{
    /**
     * @var array
     */
    private $statusStack = [];
    /**
     * @var array
     */
    private $blocks = [];
    /**
     * @var SafeAnalysisNodeVisitor
     */
    private $safeAnalysis;
    /**
     * @var
     */
    private $traverser;
    /**
     * @var bool
     */
    private $defaultStrategy = false;
    /**
     * @var array
     */
    private $safeVars = [];

    /**
     * EscaperNodeVisitor constructor.
     */
    public function __construct()
    {
        $this->safeAnalysis = new SafeAnalysisNodeVisitor();
    }

    /**
     * @param Node $node
     * @param Environment $env
     * @return Node
     * @throws \Twig\Error\RuntimeError
     */
    public function enterNode(Node $node, Environment $env): Node
    {
        if ($node instanceof ModuleNode) {
            if ($env->hasExtension(EscaperExtension::class) && $defaultStrategy = $env->getExtension(EscaperExtension::class)->getDefaultStrategy($node->getTemplateName())) {
                $this->defaultStrategy = $defaultStrategy;
            }
            $this->safeVars = [];
            $this->blocks = [];
        } elseif ($node instanceof AutoEscapeNode) {
            $this->statusStack[] = $node->getAttribute('value');
        } elseif ($node instanceof BlockNode) {
            $this->statusStack[] = isset($this->blocks[$node->getAttribute('name')]) ? $this->blocks[$node->getAttribute('name')] : $this->needEscaping($env);
        } elseif ($node instanceof ImportNode) {
            $this->safeVars[] = $node->getNode('var')->getAttribute('name');
        }

        return $node;
    }

    /**
     * @param Node $node
     * @param Environment $env
     * @return Node|null
     */
    public function leaveNode(Node $node, Environment $env): ?Node
    {
        if ($node instanceof ModuleNode) {
            $this->defaultStrategy = false;
            $this->safeVars = [];
            $this->blocks = [];
        } elseif ($node instanceof FilterExpression) {
            return $this->preEscapeFilterNode($node, $env);
        } elseif ($node instanceof PrintNode && false !== $type = $this->needEscaping($env)) {
            $expression = $node->getNode('expr');
            if ($expression instanceof ConditionalExpression && $this->shouldUnwrapConditional($expression, $env, $type)) {
                return new DoNode($this->unwrapConditional($expression, $env, $type), $expression->getTemplateLine());
            }

            return $this->escapePrintNode($node, $env, $type);
        }

        if ($node instanceof AutoEscapeNode || $node instanceof BlockNode) {
            array_pop($this->statusStack);
        } elseif ($node instanceof BlockReferenceNode) {
            $this->blocks[$node->getAttribute('name')] = $this->needEscaping($env);
        }

        return $node;
    }

    /**
     * @param ConditionalExpression $expression
     * @param Environment $env
     * @param string $type
     * @return bool
     */
    private function shouldUnwrapConditional(ConditionalExpression $expression, Environment $env, string $type): bool
    {
        $expr2Safe = $this->isSafeFor($type, $expression->getNode('expr2'), $env);
        $expr3Safe = $this->isSafeFor($type, $expression->getNode('expr3'), $env);

        return $expr2Safe !== $expr3Safe;
    }

    /**
     * @param ConditionalExpression $expression
     * @param Environment $env
     * @param string $type
     * @return ConditionalExpression
     */
    private function unwrapConditional(ConditionalExpression $expression, Environment $env, string $type): ConditionalExpression
    {
        // convert "echo a ? b : c" to "a ? echo b : echo c" recursively
        $expr2 = $expression->getNode('expr2');
        if ($expr2 instanceof ConditionalExpression && $this->shouldUnwrapConditional($expr2, $env, $type)) {
            $expr2 = $this->unwrapConditional($expr2, $env, $type);
        } else {
            $expr2 = $this->escapeInlinePrintNode(new InlinePrint($expr2, $expr2->getTemplateLine()), $env, $type);
        }
        $expr3 = $expression->getNode('expr3');
        if ($expr3 instanceof ConditionalExpression && $this->shouldUnwrapConditional($expr3, $env, $type)) {
            $expr3 = $this->unwrapConditional($expr3, $env, $type);
        } else {
            $expr3 = $this->escapeInlinePrintNode(new InlinePrint($expr3, $expr3->getTemplateLine()), $env, $type);
        }

        return new ConditionalExpression($expression->getNode('expr1'), $expr2, $expr3, $expression->getTemplateLine());
    }

    /**
     * @param InlinePrint $node
     * @param Environment $env
     * @param string $type
     * @return Node
     */
    private function escapeInlinePrintNode(InlinePrint $node, Environment $env, string $type): Node
    {
        $expression = $node->getNode('node');

        if ($this->isSafeFor($type, $expression, $env)) {
            return $node;
        }

        return new InlinePrint($this->getEscaperFilter($type, $expression), $node->getTemplateLine());
    }

    /**
     * @param PrintNode $node
     * @param Environment $env
     * @param string $type
     * @return Node
     */
    private function escapePrintNode(PrintNode $node, Environment $env, string $type): Node
    {
        if (false === $type) {
            return $node;
        }

        $expression = $node->getNode('expr');

        if ($this->isSafeFor($type, $expression, $env)) {
            return $node;
        }

        $class = \get_class($node);

        return new $class($this->getEscaperFilter($type, $expression), $node->getTemplateLine());
    }

    /**
     * @param FilterExpression $filter
     * @param Environment $env
     * @return FilterExpression
     */
    private function preEscapeFilterNode(FilterExpression $filter, Environment $env): FilterExpression
    {
        $name = $filter->getNode('filter')->getAttribute('value');

        $type = $env->getFilter($name)->getPreEscape();
        if (null === $type) {
            return $filter;
        }

        $node = $filter->getNode('node');
        if ($this->isSafeFor($type, $node, $env)) {
            return $filter;
        }

        $filter->setNode('node', $this->getEscaperFilter($type, $node));

        return $filter;
    }

    /**
     * @param string $type
     * @param Node $expression
     * @param Environment $env
     * @return bool
     */
    private function isSafeFor(string $type, Node $expression, Environment $env): bool
    {
        $safe = $this->safeAnalysis->getSafe($expression);

        if (null === $safe) {
            if (null === $this->traverser) {
                $this->traverser = new NodeTraverser($env, [$this->safeAnalysis]);
            }

            $this->safeAnalysis->setSafeVars($this->safeVars);

            $this->traverser->traverse($expression);
            $safe = $this->safeAnalysis->getSafe($expression);
        }

        return \in_array($type, $safe) || \in_array('all', $safe);
    }

    /**
     * @param Environment $env
     * @return bool|mixed
     */
    private function needEscaping(Environment $env)
    {
        if (\count($this->statusStack)) {
            return $this->statusStack[\count($this->statusStack) - 1];
        }

        return $this->defaultStrategy ? $this->defaultStrategy : false;
    }

    /**
     * @param string $type
     * @param Node $node
     * @return FilterExpression
     */
    private function getEscaperFilter(string $type, Node $node): FilterExpression
    {
        $line = $node->getTemplateLine();
        $name = new ConstantExpression('escape', $line);
        $args = new Node([new ConstantExpression((string) $type, $line), new ConstantExpression(null, $line), new ConstantExpression(true, $line)]);

        return new FilterExpression($node, $name, $args, $line);
    }

    /**
     * @return int
     */
    public function getPriority(): int
    {
        return 0;
    }
}
