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
use Twig\Node\CaptureNode;
use Twig\Node\Expression\AbstractExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\Filter\EscapeFilter;
use Twig\Node\Expression\FilterExpression;
use Twig\Node\Expression\FunctionExpression;
use Twig\Node\Expression\MacroReferenceExpression;
use Twig\Node\Expression\OperatorEscapeInterface;
use Twig\Node\ImportNode;
use Twig\Node\MacroNode;
use Twig\Node\ModuleNode;
use Twig\Node\Node;
use Twig\Node\Nodes;
use Twig\Node\PrintNode;
use Twig\Node\SetNode;
use Twig\NodeTraverser;
use Twig\TwigFilter;

/**
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @internal
 */
final class EscaperNodeVisitor implements NodeVisitorInterface
{
    private $statusStack = [];
    private $blocks = [];
    private $safeAnalysis;
    private $traverser;
    private $defaultStrategy = false;
    private $safeVars = [];
    private bool $usesEscaper = false;

    public function __construct()
    {
        $this->safeAnalysis = new SafeAnalysisNodeVisitor();
    }

    public function enterNode(Node $node, Environment $env): Node
    {
        if ($node instanceof ModuleNode) {
            $this->defaultStrategy = false;
            if ($env->hasExtension(EscaperExtension::class) && $defaultStrategy = $env->getExtension(EscaperExtension::class)->getDefaultStrategy($node->getTemplateName())) {
                $this->defaultStrategy = $defaultStrategy;
            }
            $node->setAttribute('strategy', \is_string($this->defaultStrategy) ? $this->defaultStrategy : false);
            $this->safeVars = [];
            $this->blocks = [];
            $this->statusStack = [];
            $this->usesEscaper = false;
        } elseif ($node instanceof AutoEscapeNode) {
            $this->statusStack[] = $node->getAttribute('value');
        } elseif ($node instanceof BlockNode) {
            $this->statusStack[] = $this->blocks[$node->getAttribute('name')] ?? $this->needEscaping();
        } elseif ($node instanceof ImportNode) {
            $this->safeVars[] = $node->getNode('var')->getNode('var')->getAttribute('name');
        } elseif ($node instanceof CaptureNode || $node instanceof MacroNode || ($node instanceof SetNode && $node->getAttribute('safe'))) {
            // captured content is only proven safe for the strategy it was captured under;
            // the legacy "{% autoescape true %}" strategy names no escaper, so it proves nothing
            $node->setAttribute('strategy', \is_string($strategy = $this->needEscaping()) ? $strategy : false);
        }

        return $node;
    }

    public function leaveNode(Node $node, Environment $env): ?Node
    {
        if ($node instanceof ModuleNode) {
            $node->setAttribute('escaper', $this->usesEscaper);
            $this->defaultStrategy = false;
            $this->safeVars = [];
            $this->blocks = [];
        } elseif ($node instanceof FilterExpression) {
            if ($node instanceof EscapeFilter) {
                $this->useTemplateEscaper($node);
            }

            return $this->preEscapeFilterNode($node, $env);
        } elseif ($node instanceof PrintNode && false !== $type = $this->needEscaping()) {
            $expression = $node->getNode('expr');
            if ($expression instanceof OperatorEscapeInterface) {
                $this->escapeConditional($expression, $env, $type);
            } else {
                $node->setNode('expr', $this->escapeExpression($expression, $env, $type));
            }

            return $node;
        }

        if ($node instanceof AutoEscapeNode || $node instanceof BlockNode) {
            array_pop($this->statusStack);
        } elseif ($node instanceof BlockReferenceNode) {
            $this->blocks[$node->getAttribute('name')] = $this->needEscaping();
        }

        return $node;
    }

    /**
     * @param AbstractExpression&OperatorEscapeInterface $expression
     */
    private function escapeConditional($expression, Environment $env, string $type): void
    {
        foreach ($expression->getOperandNamesToEscape() as $name) {
            /** @var AbstractExpression $operand */
            $operand = $expression->getNode($name);
            if ($operand instanceof OperatorEscapeInterface) {
                $this->escapeConditional($operand, $env, $type);
            } else {
                $expression->setNode($name, $this->escapeExpression($operand, $env, $type));
            }
        }
    }

    private function escapeExpression(AbstractExpression $expression, Environment $env, string $type): AbstractExpression
    {
        if ($this->isSafeFor($type, $expression, $env) || $this->escapeProducedContent($expression, $env, $type)) {
            return $expression;
        }

        return $this->getEscaperFilter($env, $type, $expression);
    }

    /**
     * Escapes content produced by a template or a macro before the filters preserving its safety.
     *
     * Only the produced Markup instance knows the strategy it is safe for, and these
     * filters return a plain string.
     */
    private function escapeProducedContent(AbstractExpression $expression, Environment $env, string $type): bool
    {
        if (!$expression instanceof FilterExpression || !$this->preservesSafetyFor($type, $expression, $env)) {
            return false;
        }

        /** @var AbstractExpression $node */
        $node = $expression->getNode('node');
        if (!$node instanceof MacroReferenceExpression && !($node instanceof FunctionExpression && \in_array($node->getAttribute('name'), ['include', 'include_only'], true))) {
            return $this->escapeProducedContent($node, $env, $type);
        }

        $expression->setNode('node', $this->getEscaperFilter($env, $type, $node));

        return true;
    }

    private function preservesSafetyFor(string $type, FilterExpression $expression, Environment $env): bool
    {
        $filter = $this->getFilter($expression, $env);
        if ($filter->getSafe($expression->getNode('arguments'))) {
            return false;
        }

        $preserved = SafeAnalysisNodeVisitor::addImpliedStrategies($filter->getPreservesSafety());

        return \in_array($type, $preserved, true) || \in_array('all', $preserved, true);
    }

    private function preEscapeFilterNode(FilterExpression $filter, Environment $env): FilterExpression
    {
        if (null === $type = $this->getFilter($filter, $env)->getPreEscape()) {
            return $filter;
        }

        /** @var AbstractExpression $node */
        $node = $filter->getNode('node');
        $filter->setNode('node', $this->escapeExpression($node, $env, $type));

        return $filter;
    }

    private function getFilter(FilterExpression $filter, Environment $env): TwigFilter
    {
        if ($filter->hasAttribute('twig_callable')) {
            return $filter->getAttribute('twig_callable');
        }

        // legacy
        return $env->getFilter($filter->getNode('filter', false)->getAttribute('value'));
    }

    private function isSafeFor(string $type, AbstractExpression $expression, Environment $env): bool
    {
        $safe = $this->safeAnalysis->getSafe($expression);

        if (!$safe) {
            if (null === $this->traverser) {
                $this->traverser = new NodeTraverser($env, [$this->safeAnalysis]);
            }

            $this->safeAnalysis->setSafeVars($this->safeVars);

            $this->traverser->traverse($expression);
            $safe = $this->safeAnalysis->getSafe($expression);
        }

        return \in_array($type, $safe, true) || \in_array('all', $safe, true);
    }

    /**
     * @return string|false
     */
    private function needEscaping(): string|bool
    {
        if (\count($this->statusStack)) {
            return $this->statusStack[\count($this->statusStack) - 1];
        }

        return $this->defaultStrategy ?: false;
    }

    private function getEscaperFilter(Environment $env, string $type, AbstractExpression $node): FilterExpression
    {
        $line = $node->getTemplateLine();
        $filter = $env->getFilter('escape');
        $args = new Nodes([new ConstantExpression($type, $line), new ConstantExpression(null, $line), new ConstantExpression(true, $line)]);
        $class = $filter->getNodeClass();
        $expression = new $class($node, $filter, $args, $line);

        if ($expression instanceof EscapeFilter) {
            $this->useTemplateEscaper($expression);
        }

        return $expression;
    }

    private function useTemplateEscaper(EscapeFilter $filter): void
    {
        $filter->setAttribute('template_escaper', true);
        $this->usesEscaper = true;
    }

    public function getPriority(): int
    {
        return 0;
    }
}
