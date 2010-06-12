<?php

/*
 * This file is part of Twig.
 *
 * (c) 2009 Fabien Potencier
 * (c) 2009 Armin Ronacher
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Represents a for node.
 *
 * @package    twig
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
class Twig_Node_For extends Twig_Node
{
    public function __construct(Twig_Node_Expression_AssignName $keyTarget, Twig_Node_Expression_AssignName $valueTarget, Twig_Node_Expression $seq, Twig_NodeInterface $body, Twig_NodeInterface $else = null, $withLoop = false, $lineno, $tag = null)
    {
        parent::__construct(array('key_target' => $keyTarget, 'value_target' => $valueTarget, 'seq' => $seq, 'body' => $body, 'else' => $else), array('with_loop' => $withLoop), $lineno, $tag);
    }

    /**
     * Compiles the node to PHP.
     *
     * @param Twig_Compiler A Twig_Compiler instance
     */
    public function compile($compiler)
    {
        $compiler
            ->addDebugInfo($this)
            // the (array) cast bypasses a PHP 5.2.6 bug
            ->write('$context[\'_parent\'] = (array) $context;'."\n")
        ;

        if (!is_null($this->else)) {
            $compiler->write("\$context['_iterated'] = false;\n");
        }

        $compiler
            ->write("\$context['_seq'] = twig_iterator_to_array(")
            ->subcompile($this->seq)
            ->raw(");\n")
        ;

        if ($this['with_loop']) {
            $compiler
                ->write("\$countable = is_array(\$context['_seq']) || (is_object(\$context['_seq']) && \$context['_seq'] instanceof Countable);\n")
                ->write("\$length = \$countable ? count(\$context['_seq']) : null;\n")

                ->write("\$context['loop'] = array(\n")
                ->write("  'parent' => \$context['_parent'],\n")
                ->write("  'index0' => 0,\n")
                ->write("  'index'  => 1,\n")
                ->write("  'first'  => true,\n")
                ->write(");\n")
                ->write("if (\$countable) {\n")
                ->indent()
                ->write("\$context['loop']['revindex0'] = \$length - 1;\n")
                ->write("\$context['loop']['revindex'] = \$length;\n")
                ->write("\$context['loop']['length'] = \$length;\n")
                ->write("\$context['loop']['last'] = 1 === \$length;\n")
                ->outdent()
                ->write("}\n")
            ;
        }

        $compiler
            ->write("foreach (\$context['_seq'] as ")
            ->subcompile($this->key_target)
            ->raw(" => ")
            ->subcompile($this->value_target)
            ->raw(") {\n")
            ->indent()
        ;

        if (!is_null($this->else)) {
            $compiler->write("\$context['_iterated'] = true;\n");
        }

        $compiler->subcompile($this->body);

        if ($this['with_loop']) {
            $compiler
                ->write("++\$context['loop']['index0'];\n")
                ->write("++\$context['loop']['index'];\n")
                ->write("\$context['loop']['first'] = false;\n")
                ->write("if (\$countable) {\n")
                ->indent()
                ->write("--\$context['loop']['revindex0'];\n")
                ->write("--\$context['loop']['revindex'];\n")
                ->write("\$context['loop']['last'] = 0 === \$context['loop']['revindex0'];\n")
                ->outdent()
                ->write("}\n")
            ;
        }

        $compiler
            ->outdent()
            ->write("}\n")
        ;

        if (!is_null($this->else)) {
            $compiler
                ->write("if (!\$context['_iterated']) {\n")
                ->indent()
                ->subcompile($this->else)
                ->outdent()
                ->write("}\n")
            ;
        }

        $compiler->write('$_parent = $context[\'_parent\'];'."\n");

        // remove some "private" loop variables (needed for nested loops)
        $compiler->write('unset($context[\'_seq\'], $context[\'_iterated\'], $context[\''.$this->key_target['name'].'\'], $context[\''.$this->value_target['name'].'\'], $context[\'_parent\'], $context[\'loop\']);'."\n");

        /// keep the values set in the inner context for variables defined in the outer context
        $compiler->write('$context = array_merge($_parent, array_intersect_key($context, $_parent));'."\n");
    }
}
