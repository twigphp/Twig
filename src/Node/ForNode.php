<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 * (c) Armin Ronacher
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Node;

use Twig\Compiler;
use Twig\Node\Expression\AbstractExpression;
use Twig\Node\Expression\ArrowFunctionExpression;
use Twig\Node\Expression\Variable\AssignContextVariable;

/**
 * Represents a for node.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class ForNode extends Node
{
    public function __construct(AssignContextVariable $keyTarget, AssignContextVariable $valueTarget, AbstractExpression $seq, ?AbstractExpression $ifexpr, Node $body, ?ForElseNode $else, int $lineno)
    {
        $nodes = ['key_target' => $keyTarget, 'value_target' => $valueTarget, 'seq' => $seq];
        if (null !== $ifexpr) {
            $nodes['ifexpr'] = $ifexpr;
        }
        $nodes['body'] = $body;
        if (null !== $else) {
            $nodes['else'] = $else;
        }

        parent::__construct($nodes, ['with_loop' => true, 'with_recursion' => true], $lineno);
    }

    public function compile(Compiler $compiler): void
    {
        if ($this->getAttribute('with_loop') && $this->getAttribute('with_recursion')) {
            $this->compileWithLoop($compiler);
        } else {
            $this->compileInline($compiler, $this->getAttribute('with_loop'));
        }
    }

    /**
     * When the loop() function cannot recurse into the body, the body needs no closure.
     */
    private function compileInline(Compiler $compiler, bool $withLoop): void
    {
        $seqVar = $compiler->getVarName();
        $parentVar = $compiler->getVarName();
        $iteratedVar = $this->hasNode('else') ? $compiler->getVarName() : null;

        $compiler->addDebugInfo($this);

        if ($withLoop) {
            $compiler
                ->write("\$$seqVar = new \\Twig\\Runtime\\LoopIterator(")
                ->subcompile($this->getNode('seq'))
                ->raw(");\n")
                ->write("\$$parentVar = \$context;\n")
                ->write("\$context['loop'] = new \\Twig\\Runtime\\LoopContext(\$$seqVar, \$$parentVar, \$blocks, null, 0);\n")
            ;
        } else {
            $compiler
                ->write("\$$seqVar = ")
                ->subcompile($this->getNode('seq'))
                ->raw(";\n")
                ->write("\$$parentVar = \$context;\n")
            ;
        }

        if (null !== $iteratedVar) {
            $compiler->write("\$$iteratedVar = false;\n");
        }

        $compiler
            ->write($withLoop ? "foreach (\$$seqVar as " : "foreach (\\is_array(\$$seqVar) || \$$seqVar instanceof \\Traversable ? \$$seqVar : [] as ")
            ->subcompile($this->getNode('key_target'))
            ->raw(' => ')
            ->subcompile($this->getNode('value_target'))
            ->raw(") {\n")
            ->indent()
        ;

        $this->compileBody($compiler, $iteratedVar);

        $this->compileContextRestore($compiler, $parentVar, $withLoop);

        $compiler->write("unset(\$$seqVar, \$$parentVar".(null !== $iteratedVar ? ", \$$iteratedVar" : '').");\n");
    }

    private function compileWithLoop(Compiler $compiler): void
    {
        $iteratorVar = $compiler->getVarName();
        $functionVar = $compiler->getVarName();

        $compiler
            ->addDebugInfo($this)
            ->write("\$$iteratorVar = new \Twig\Runtime\LoopIterator(")
            ->subcompile($this->getNode('seq'))
            ->raw(");\n")
            ->write("yield from (\$$functionVar = function (\$iterator, &\$context, \$blocks, \$recurseFunc, \$depth) use (&\$macros) {\n")
            ->indent()
            ->write("\$parent = \$context;\n")
            ->write("\$context['loop'] = new \Twig\Runtime\LoopContext(\$iterator, \$parent, \$blocks, \$recurseFunc, \$depth);\n")
        ;

        if ($this->hasNode('else')) {
            $compiler->write("\$iterated = false;\n");
        }

        $compiler
            ->write('foreach ($iterator as ')
            ->subcompile($this->getNode('key_target'))
            ->raw(' => ')
            ->subcompile($this->getNode('value_target'))
            ->raw(") {\n")
            ->indent()
        ;

        $this->compileBody($compiler, $this->hasNode('else') ? 'iterated' : null);

        $this->compileContextRestore($compiler, 'parent', true);

        $compiler
            ->write("return; yield;\n")
            ->outdent()
            ->write("})(\$$iteratorVar, \$context, \$blocks, \$$functionVar, 0);\n")
        ;
    }

    private function compileContextRestore(Compiler $compiler, string $parentVar, bool $withLoop): void
    {
        if (!self::setsVariables($this)) {
            $compiler->write("\$context = \$$parentVar;\n");

            return;
        }

        // keep the values set in the loop for the variables defined before it
        $compiler
            ->write('unset($context[\''.$this->getNode('key_target')->getAttribute('name').'\'], $context[\''.$this->getNode('value_target')->getAttribute('name').'\']'.($withLoop ? ', $context[\'loop\']' : '').');'."\n")
            ->write("\$context = array_intersect_key(\$context, \$$parentVar) + \$$parentVar;\n")
        ;
    }

    /**
     * Returns whether the node can set variables other than the ones defined by loops.
     */
    private static function setsVariables(Node $node): bool
    {
        if ($node instanceof AssignContextVariable) {
            return true;
        }

        // an arrow function sets its arguments in its own copy of the context
        if ($node instanceof ArrowFunctionExpression) {
            return false;
        }

        // the compiled code of a tag that Twig does not know could change the context
        if (!$node instanceof AbstractExpression && (!str_starts_with($node::class, 'Twig\\') || str_contains($node::class, '@anonymous'))) {
            return true;
        }

        foreach ($node as $name => $child) {
            if ($node instanceof self && \in_array($name, ['key_target', 'value_target'], true)) {
                continue;
            }

            if (self::setsVariables($child)) {
                return true;
            }
        }

        return false;
    }

    private function compileBody(Compiler $compiler, ?string $iteratedVar): void
    {
        if ($this->hasNode('ifexpr')) {
            $compiler
                ->write('if (!(')
                ->subcompile($this->getNode('ifexpr'))
                ->raw(")) {\n")
                ->indent()
                ->write("continue;\n")
                ->outdent()
                ->write("}\n")
            ;
        }

        if (null !== $iteratedVar) {
            $compiler->write("\$$iteratedVar = true;\n");
        }

        $compiler
            ->subcompile($this->getNode('body'))
            ->outdent()
            ->write("}\n")
        ;

        if (null !== $iteratedVar) {
            $compiler
                ->addDebugInfo($this->getNode('else'))
                ->write("if (!\$$iteratedVar) {\n")
                ->indent()
                ->subcompile($this->getNode('else'))
                ->outdent()
                ->write("}\n")
            ;
        }
    }
}
