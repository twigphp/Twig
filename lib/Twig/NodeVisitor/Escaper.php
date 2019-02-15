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
 * Twig_NodeVisitor_Escaper implements output escaping.
 *
 * @final
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Twig_NodeVisitor_Escaper extends \Twig\NodeVisitor\AbstractNodeVisitor
{
    protected $statusStack = [];
    protected $blocks = [];
    protected $safeAnalysis;
    protected $traverser;
    protected $defaultStrategy = false;
    protected $safeVars = [];

    public function __construct()
    {
        $this->safeAnalysis = new \Twig\NodeVisitor\SafeAnalysisNodeVisitor();
    }

    protected function doEnterNode(\Twig\Node\Node $node, \Twig\Environment $env)
    {
        if ($node instanceof \Twig\Node\ModuleNode) {
            if ($env->hasExtension('\Twig\Extension\EscaperExtension') && $defaultStrategy = $env->getExtension('\Twig\Extension\EscaperExtension')->getDefaultStrategy($node->getTemplateName())) {
                $this->defaultStrategy = $defaultStrategy;
            }
            $this->safeVars = [];
            $this->blocks = [];
        } elseif ($node instanceof \Twig\Node\AutoEscapeNode) {
            $this->statusStack[] = $node->getAttribute('value');
        } elseif ($node instanceof \Twig\Node\BlockNode) {
            $this->statusStack[] = isset($this->blocks[$node->getAttribute('name')]) ? $this->blocks[$node->getAttribute('name')] : $this->needEscaping($env);
        } elseif ($node instanceof \Twig\Node\ImportNode) {
            $this->safeVars[] = $node->getNode('var')->getAttribute('name');
        }

        return $node;
    }

    protected function doLeaveNode(\Twig\Node\Node $node, \Twig\Environment $env)
    {
        if ($node instanceof \Twig\Node\ModuleNode) {
            $this->defaultStrategy = false;
            $this->safeVars = [];
            $this->blocks = [];
        } elseif ($node instanceof \Twig\Node\Expression\FilterExpression) {
            return $this->preEscapeFilterNode($node, $env);
        } elseif ($node instanceof \Twig\Node\PrintNode) {
            return $this->escapePrintNode($node, $env, $this->needEscaping($env));
        }

        if ($node instanceof \Twig\Node\AutoEscapeNode || $node instanceof \Twig\Node\BlockNode) {
            array_pop($this->statusStack);
        } elseif ($node instanceof \Twig\Node\BlockReferenceNode) {
            $this->blocks[$node->getAttribute('name')] = $this->needEscaping($env);
        }

        return $node;
    }

    protected function escapePrintNode(\Twig\Node\PrintNode $node, \Twig\Environment $env, $type)
    {
        if (false === $type) {
            return $node;
        }

        $expression = $node->getNode('expr');

        if ($this->isSafeFor($type, $expression, $env)) {
            return $node;
        }

        $class = \get_class($node);

        return new $class(
            $this->getEscaperFilter($type, $expression),
            $node->getTemplateLine()
        );
    }

    protected function preEscapeFilterNode(\Twig\Node\Expression\FilterExpression $filter, \Twig\Environment $env)
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

    protected function isSafeFor($type, Twig_NodeInterface $expression, $env)
    {
        $safe = $this->safeAnalysis->getSafe($expression);

        if (null === $safe) {
            if (null === $this->traverser) {
                $this->traverser = new \Twig\NodeTraverser($env, [$this->safeAnalysis]);
            }

            $this->safeAnalysis->setSafeVars($this->safeVars);

            $this->traverser->traverse($expression);
            $safe = $this->safeAnalysis->getSafe($expression);
        }

        return \in_array($type, $safe) || \in_array('all', $safe);
    }

    protected function needEscaping(\Twig\Environment $env)
    {
        if (\count($this->statusStack)) {
            return $this->statusStack[\count($this->statusStack) - 1];
        }

        return $this->defaultStrategy ? $this->defaultStrategy : false;
    }

    protected function getEscaperFilter($type, Twig_NodeInterface $node)
    {
        $line = $node->getTemplateLine();
        $name = new \Twig\Node\Expression\ConstantExpression('escape', $line);
        $args = new \Twig\Node\Node([new \Twig\Node\Expression\ConstantExpression((string) $type, $line), new \Twig\Node\Expression\ConstantExpression(null, $line), new \Twig\Node\Expression\ConstantExpression(true, $line)]);

        return new \Twig\Node\Expression\FilterExpression($node, $name, $args, $line);
    }

    public function getPriority()
    {
        return 0;
    }
}

class_alias('Twig_NodeVisitor_Escaper', 'Twig\NodeVisitor\EscaperNodeVisitor', false);
