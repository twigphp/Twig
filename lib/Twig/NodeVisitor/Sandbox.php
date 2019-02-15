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
 * Twig_NodeVisitor_Sandbox implements sandboxing.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
final class Twig_NodeVisitor_Sandbox extends \Twig\NodeVisitor\AbstractNodeVisitor
{
    private $inAModule = false;
    private $tags;
    private $filters;
    private $functions;

    protected function doEnterNode(\Twig\Node\Node $node, \Twig\Environment $env)
    {
        if ($node instanceof \Twig\Node\ModuleNode) {
            $this->inAModule = true;
            $this->tags = [];
            $this->filters = [];
            $this->functions = [];

            return $node;
        } elseif ($this->inAModule) {
            // look for tags
            if ($node->getNodeTag() && !isset($this->tags[$node->getNodeTag()])) {
                $this->tags[$node->getNodeTag()] = $node;
            }

            // look for filters
            if ($node instanceof \Twig\Node\Expression\FilterExpression && !isset($this->filters[$node->getNode('filter')->getAttribute('value')])) {
                $this->filters[$node->getNode('filter')->getAttribute('value')] = $node;
            }

            // look for functions
            if ($node instanceof \Twig\Node\Expression\FunctionExpression && !isset($this->functions[$node->getAttribute('name')])) {
                $this->functions[$node->getAttribute('name')] = $node;
            }

            // the .. operator is equivalent to the range() function
            if ($node instanceof \Twig\Node\Expression\Binary\RangeBinary && !isset($this->functions['range'])) {
                $this->functions['range'] = $node;
            }

            // wrap print to check __toString() calls
            if ($node instanceof \Twig\Node\PrintNode) {
                return new \Twig\Node\SandboxedPrintNode($node->getNode('expr'), $node->getTemplateLine(), $node->getNodeTag());
            }
        }

        return $node;
    }

    protected function doLeaveNode(\Twig\Node\Node $node, \Twig\Environment $env)
    {
        if ($node instanceof \Twig\Node\ModuleNode) {
            $this->inAModule = false;

            $node->setNode('display_start', new \Twig\Node\Node([new \Twig\Node\CheckSecurityNode($this->filters, $this->tags, $this->functions), $node->getNode('display_start')]));
        }

        return $node;
    }

    public function getPriority()
    {
        return 0;
    }
}

class_alias('Twig_NodeVisitor_Sandbox', 'Twig\NodeVisitor\SandboxNodeVisitor', false);
