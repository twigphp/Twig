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

use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Node\Expression\AbstractExpression;
use Twig\Node\Expression\Variable\AssignContextVariable;

/**
 * Represents a for node.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
#[YieldReady]
class ForNode extends Node
{
    public function __construct(AssignContextVariable $keyTarget, AssignContextVariable $valueTarget, AbstractExpression $seq, ?AbstractExpression $ifexpr, Node $body, ?ForElseNode $else, int $lineno)
    {
        if ($ifexpr) {
            $body = new IfNode(new Nodes([$ifexpr, $body]), null, $lineno);
        }

        $nodes = ['key_target' => $keyTarget, 'value_target' => $valueTarget, 'seq' => $seq, 'body' => $body];
        if (null !== $else) {
            $nodes['else'] = $else;
        }

        parent::__construct($nodes, ['with_loop' => true], $lineno);
    }

    public function compile(Compiler $compiler): void
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
        ;

        if ($this->getAttribute('with_loop')) {
            $compiler->write("\$context['loop'] = new \Twig\Runtime\LoopContext(\$iterator, \$parent, \$blocks, \$recurseFunc, \$depth);\n");
        }

        $compiler
            ->write('foreach ($iterator as ')
            ->subcompile($this->getNode('key_target'))
            ->raw(' => ')
            ->subcompile($this->getNode('value_target'))
            ->raw(") {\n")
            ->indent()
            ->subcompile($this->getNode('body'))
            ->outdent()
            ->write("}\n")
        ;

        if ($this->hasNode('else')) {
            $compiler->subcompile($this->getNode('else'));
        }

        // remove some "private" loop variables (needed for nested loops)
        $compiler->write('unset($context[\''.$this->getNode('key_target')->getAttribute('name').'\'], $context[\''.$this->getNode('value_target')->getAttribute('name').'\']');
        if ($this->getAttribute('with_loop')) {
            $compiler->raw(', $context[\'loop\']');
        }
        $compiler->raw(");\n");

        // keep the values set in the inner context for variables defined in the outer context
        $compiler->write("\$context = array_intersect_key(\$context, \$parent) + \$parent;\n");

        $compiler
            ->write("yield from [];\n")
            ->outdent()
            ->write("})(\$$iteratorVar, \$context, \$blocks, \$$functionVar, 0);\n")
        ;
    }
}
