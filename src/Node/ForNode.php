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

        parent::__construct($nodes, ['with_loop' => true], $lineno);
    }

    public function compile(Compiler $compiler): void
    {
        if ($this->getAttribute('with_loop')) {
            $this->compileWithLoop($compiler);
        } else {
            $this->compileInline($compiler);
        }
    }

    /**
     * Without the loop variable, nothing can recurse into the body, which therefore needs no closure.
     */
    private function compileInline(Compiler $compiler): void
    {
        $seqVar = $compiler->getVarName();
        $parentVar = $compiler->getVarName();
        $iteratedVar = $this->hasNode('else') ? $compiler->getVarName() : null;

        $compiler
            ->addDebugInfo($this)
            ->write("\$$seqVar = ")
            ->subcompile($this->getNode('seq'))
            ->raw(";\n")
            ->write("\$$parentVar = \$context;\n")
        ;

        if (null !== $iteratedVar) {
            $compiler->write("\$$iteratedVar = false;\n");
        }

        $compiler
            ->write("foreach (\\is_array(\$$seqVar) || \$$seqVar instanceof \\Traversable ? \$$seqVar : [] as ")
            ->subcompile($this->getNode('key_target'))
            ->raw(' => ')
            ->subcompile($this->getNode('value_target'))
            ->raw(") {\n")
            ->indent()
        ;

        $this->compileBody($compiler, $iteratedVar);

        // the context is left untouched when the sequence is empty and the else clause sets nothing
        $compiler
            ->write("if (\$context !== \$$parentVar) {\n")
            ->indent()
            ->write('unset($context[\''.$this->getNode('key_target')->getAttribute('name').'\'], $context[\''.$this->getNode('value_target')->getAttribute('name').'\']);'."\n")
            ->write("\$context = array_intersect_key(\$context, \$$parentVar) + \$$parentVar;\n")
            ->outdent()
            ->write("}\n")
            ->write("unset(\$$seqVar, \$$parentVar".(null !== $iteratedVar ? ", \$$iteratedVar" : '').");\n")
        ;
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
            ->write("yield from (\$$functionVar = function (\$iterator, &\$context, \$blocks, \$recurseFunc, \$depth) {\n")
            ->indent()
            ->write("\$macros = \$this->macros;\n")
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

        // remove some "private" loop variables (needed for nested loops)
        $compiler->write('unset($context[\''.$this->getNode('key_target')->getAttribute('name').'\'], $context[\''.$this->getNode('value_target')->getAttribute('name').'\'], $context[\'loop\']);'."\n");

        // keep the values set in the inner context for variables defined in the outer context
        $compiler->write("\$context = array_intersect_key(\$context, \$parent) + \$parent;\n");

        $compiler
            ->write("return; yield;\n")
            ->outdent()
            ->write("})(\$$iteratorVar, \$context, \$blocks, \$$functionVar, 0);\n")
        ;
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
            $this->getNode('else')->setAttribute('iterated_var', $iteratedVar);
            $compiler->subcompile($this->getNode('else'));
        }
    }
}
