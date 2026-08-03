<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Node;

use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Node\Expression\Variable\AssignMacroVariable;

/**
 * Compiles the lazy loader for the top-level imports used by the macros
 * declared in a template.
 *
 * @internal
 */
#[YieldReady]
final class MacroImportsNode extends Node
{
    public function __construct(Node $body)
    {
        $imports = [];
        $this->collectTopLevelImports($body, $imports);

        parent::__construct($imports);
    }

    public function compile(Compiler $compiler): void
    {
        $compiler
            ->raw("function (): void {\n")
            ->indent()
            ->write("if (\$this->skipLazyMacroImports) {\n")
            ->indent()
            ->write("return;\n")
            ->outdent()
            ->write("}\n\n")
            ->write("\$this->ensureSecurityChecked();\n")
            ->write("\$context = \$this->env->getGlobals();\n")
            ->write("\$macros = \$this->macros;\n")
        ;
        foreach ($this as $import) {
            $compiler->subcompile($import);
        }
        $compiler
            ->outdent()
            ->write('}')
        ;
    }

    /**
     * @param list<ImportNode> $imports
     */
    private function collectTopLevelImports(Node $node, array &$imports): void
    {
        if ($node instanceof ImportNode) {
            $var = $node->getNode('var');
            if ($var instanceof AssignMacroVariable && $var->getAttribute('global')) {
                $imports[] = $node;
            }

            return;
        }

        if (!$node instanceof BodyNode && !$node instanceof Nodes) {
            return;
        }

        foreach ($node as $child) {
            $this->collectTopLevelImports($child, $imports);
        }
    }
}
