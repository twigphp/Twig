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
use Twig\Node\Expression\AssignNameExpression;

/**
 * Represents a for node.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
#[YieldReady]
class ForNode extends Node
{
    public function __construct(AssignNameExpression $keyTarget, AssignNameExpression $valueTarget, AbstractExpression $seq, ?Node $ifexpr, Node $body, ?Node $else, int $lineno, ?string $tag = null)
    {
        $nodes = ['key_target' => $keyTarget, 'value_target' => $valueTarget, 'seq' => $seq, 'body' => $body];
        if (null !== $else) {
            $nodes['else'] = $else;
        }

        parent::__construct($nodes, ['with_loop' => true], $lineno, $tag);
    }

    public function compile(Compiler $compiler): void
    {
        $iteratorVar = $compiler->getVarName();

        $compiler
            ->addDebugInfo($this)
            ->write("\$context['_parent'] = \$context;\n")
            ->write("\$$iteratorVar = new \Twig\Runtime\LoopIterator(")
            ->subcompile($this->getNode('seq'))
            ->raw(");\n")
        ;

        if ($this->getAttribute('with_loop')) {
            $compiler->write("\$context['loop'] = new \Twig\Runtime\LoopContext(\$$iteratorVar, \$context['_parent']);\n");
        }

        $compiler
            ->write("foreach (\$$iteratorVar as ")
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
            $compiler
                ->write("if (0 === \${$iteratorVar}->getIndex0()) {\n")
                ->indent()
                ->subcompile($this->getNode('else'))
                ->outdent()
                ->write("}\n")
            ;
        }

        $compiler->write("\$_parent = \$context['_parent'];\n");

        // remove some "private" loop variables (needed for nested loops)
        $compiler->write('unset($context[\''.$this->getNode('key_target')->getAttribute('name').'\'], $context[\''.$this->getNode('value_target')->getAttribute('name').'\'], $context[\'_parent\'], $context[\'loop\']);'."\n");

        // keep the values set in the inner context for variables defined in the outer context
        $compiler->write("\$context = array_intersect_key(\$context, \$_parent) + \$_parent;\n");
    }
}
