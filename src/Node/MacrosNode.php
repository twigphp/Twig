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

/**
 * Represents the macros declared in a template.
 *
 * It compiles the macro namespace of the template.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
#[YieldReady]
final class MacrosNode extends Node
{
    /**
     * @param array<string, MacroNode> $macros
     */
    public function __construct(array $macros = [], private ?MacroImportsNode $imports = null)
    {
        foreach ($macros as $name => $macro) {
            if (!$macro instanceof MacroNode) {
                throw new \InvalidArgumentException(\sprintf('Using "%s" for the macro "%s" of "%s" is not supported. You must pass a "%s" instance.', get_debug_type($macro), $name, static::class, MacroNode::class));
            }
        }

        parent::__construct($macros);
    }

    public function __clone()
    {
        parent::__clone();
        if (null !== $this->imports) {
            $this->imports = clone $this->imports;
        }
    }

    public function setNode(string $name, Node $node): void
    {
        if (!$node instanceof MacroNode) {
            throw new \LogicException(\sprintf('A "%s" can only contain "%s" nodes; replacing the macro "%s" with a "%s" node is not supported.', static::class, MacroNode::class, $name, get_debug_type($node)));
        }

        parent::setNode($name, $node);
    }

    /**
     * @internal
     */
    public function hasImports(): bool
    {
        return \count($this) && null !== $this->imports && \count($this->imports);
    }

    public function compile(Compiler $compiler): void
    {
        if (!\count($this)) {
            return;
        }

        $compiler->write("private ?MacroNamespace \$macroNamespace = null;\n");
        if ($this->hasImports()) {
            $compiler->write("private bool \$skipLazyMacroImports = false;\n");
        }
        $compiler
            ->raw("\n")
            ->write("public function getMacroNamespace(): MacroNamespace\n", "{\n")
            ->indent()
            ->write('return $this->macroNamespace ??= new MacroNamespace($this, ')
        ;

        $this->compileMacros($compiler);

        if ($this->hasImports()) {
            $compiler
                ->raw(', ')
                ->subcompile($this->imports)
            ;
        }

        $compiler
            ->raw(");\n")
            ->outdent()
            ->write("}\n\n")
        ;
    }

    private function compileMacros(Compiler $compiler): void
    {
        $compiler
            ->raw("[\n")
            ->indent()
        ;

        /** @var MacroNode $macro */
        foreach ($this as $macro) {
            $compiler
                ->write('')
                ->string($macro->getAttribute('name'))
                ->raw(' => ')
            ;
            $compiler->subcompile($macro);
            $compiler->raw(",\n");
        }

        $compiler
            ->outdent()
            ->write(']')
        ;
    }
}
