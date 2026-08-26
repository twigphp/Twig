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
 * Compiles the lazy resolvers for top-level imports referenced by macros.
 *
 * @internal
 */
#[YieldReady]
final class MacroImportsNode extends Node
{
    public function __construct(Node $body)
    {
        parent::__construct($this->collectTopLevelImports($body));
    }

    public function compile(Compiler $compiler): void
    {
        $compiler
            ->write("private array \$lazyMacroImports = [];\n")
            ->write("private array \$loadingLazyMacroImports = [];\n\n")
            ->write("private function loadLazyMacroImport(int \$index): MacroNamespace\n", "{\n")
            ->indent()
            ->write("if (isset(\$this->lazyMacroImports[\$index])) {\n")
            ->indent()
            ->write("return \$this->lazyMacroImports[\$index];\n")
            ->outdent()
            ->write("}\n")
            ->write("if (isset(\$this->loadingLazyMacroImports[\$index])) {\n")
            ->indent()
            ->write("throw new RuntimeError(sprintf('A circular macro import was detected in template \"%s\".', \$this->getTemplateName()));\n")
            ->outdent()
            ->write("}\n\n")
            ->write("\$this->loadingLazyMacroImports[\$index] = true;\n")
            ->write("try {\n")
            ->indent()
            ->write("\$this->ensureSecurityChecked();\n")
            ->write("\$context = \$this->env->getGlobals();\n")
            ->write("\$macros = \$this->macros;\n\n")
            ->write("return \$this->lazyMacroImports[\$index] = match (\$index) {\n")
            ->indent()
        ;

        /** @var ImportNode $import */
        foreach ($this as $index => $import) {
            $compiler
                ->write($index.' => ')
            ;
            $import->compileMacroNamespace($compiler);
            $compiler->raw(",\n");
        }

        $compiler
            ->write("default => throw new \\LogicException(sprintf('Unknown lazy macro import %d.', \$index)),\n")
            ->outdent()
            ->write("};\n")
            ->outdent()
            ->write("} finally {\n")
            ->indent()
            ->write("unset(\$this->loadingLazyMacroImports[\$index]);\n")
            ->outdent()
            ->write("}\n")
            ->outdent()
            ->write("}\n\n")
        ;
    }

    /**
     * @return array<int, ImportNode>
     */
    private function collectTopLevelImports(Node $node): array
    {
        if ($node instanceof ImportNode) {
            $var = $node->getNode('var');

            return $var instanceof AssignMacroVariable && $var->getAttribute('global') && $var->hasAttribute('used_in_macro') ? [$var->getAttribute('macro_import_id') => $node] : [];
        }

        if (!$node instanceof BodyNode && !$node instanceof Nodes) {
            return [];
        }

        $imports = [];
        foreach ($node as $child) {
            $imports += $this->collectTopLevelImports($child);
        }

        return $imports;
    }
}
