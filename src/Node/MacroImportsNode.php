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
    /** @var array<int, true> */
    private array $supportedImports = [];

    public function __construct(Node $body)
    {
        $imports = [];
        $this->collectImports($body, true, $imports);

        parent::__construct($imports);
    }

    public function compile(Compiler $compiler): void
    {
        $compiler
            ->write("private array \$lazyMacroImports = [];\n")
            ->write("private array \$loadingLazyMacroImports = [];\n\n")
            ->write("private function loadLazyMacroImport(int \$index, int \$line): MacroNamespace\n", "{\n")
            ->indent()
            ->write("if (isset(\$this->lazyMacroImports[\$index])) {\n")
            ->indent()
            ->write("return \$this->lazyMacroImports[\$index];\n")
            ->outdent()
            ->write("}\n")
            ->write("if (isset(\$this->loadingLazyMacroImports[\$index])) {\n")
            ->indent()
            ->write("throw new RuntimeError('A circular macro import was detected.', \$line, \$this->getSourceContext());\n")
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
            if (!isset($this->supportedImports[$index])) {
                continue;
            }

            $compiler
                ->write($index.' => ')
            ;
            $import->compileMacroNamespace($compiler);
            $compiler->raw(",\n");
        }

        $compiler
            ->write("default => throw new RuntimeError('A macro import nested in a control structure cannot be resolved before the template is rendered.', \$line, \$this->getSourceContext()),\n")
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
     * @param array<int, ImportNode> $imports
     */
    private function collectImports(Node $node, bool $supported, array &$imports): void
    {
        if ($node instanceof ImportNode) {
            $var = $node->getNode('var');
            if ($var instanceof AssignMacroVariable && $var->getAttribute('global') && $var->hasAttribute('used_in_macro')) {
                $index = $var->getAttribute('macro_import_id');
                $imports[$index] = $node;
                if ($supported) {
                    $this->supportedImports[$index] = true;
                }
            }

            return;
        }

        $supported = $supported && ($node instanceof BodyNode || $node instanceof Nodes);
        foreach ($node as $child) {
            $this->collectImports($child, $supported, $imports);
        }
    }
}
