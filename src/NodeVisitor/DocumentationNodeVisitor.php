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
use Twig\Node\BlockReferenceNode;
use Twig\Node\MacroDeclarationNode;
use Twig\Node\ModuleNode;
use Twig\Node\Node;
use Twig\Node\NodeDocumentation;

/**
 * @internal
 */
final class DocumentationNodeVisitor implements NodeVisitorInterface
{
    /** @var list<ModuleNode> */
    private array $modules = [];
    /** @var list<array<string, MacroDeclarationNode>> */
    private array $macroDeclarations = [];

    public function enterNode(Node $node, Environment $env): Node
    {
        if ($node instanceof ModuleNode) {
            $this->modules[] = $node;
            $this->macroDeclarations[] = [];
        }

        $module = end($this->modules);
        if ($node instanceof BlockReferenceNode && $module->getNode('blocks')->hasNode($name = $node->getAttribute('name'))) {
            NodeDocumentation::move($node, $module->getNode('blocks')->getNode($name)->getNode('0'));
        }

        if ($node instanceof MacroDeclarationNode && $module->getNode('macros')->hasNode($name = $node->getAttribute('name'))) {
            $macro = $module->getNode('macros')->getNode($name);
            $index = array_key_last($this->macroDeclarations);
            if ($node->getTemplateLine() === $macro->getTemplateLine()) {
                if (isset($this->macroDeclarations[$index][$name])) {
                    $this->macroDeclarations[$index][$name]->setDocumentation(null);
                }
                $this->macroDeclarations[$index][$name] = $node;
            } else {
                $node->setDocumentation(null);
            }
        }

        return $node;
    }

    public function leaveNode(Node $node, Environment $env): Node
    {
        if ($node instanceof ModuleNode) {
            foreach (array_pop($this->macroDeclarations) as $name => $declaration) {
                NodeDocumentation::move($declaration, $node->getNode('macros')->getNode($name));
            }
            array_pop($this->modules);
        }

        return $node;
    }

    public function getPriority(): int
    {
        return -512;
    }
}
