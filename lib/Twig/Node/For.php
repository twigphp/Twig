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
 */
class Twig_Node_For extends Twig_Node
{
    public function __construct(Twig_Node_Expression_AssignName $keyTarget, Twig_Node_Expression_AssignName $valueTarget, Twig_Node_Expression $seq, Twig_NodeInterface $body, Twig_NodeInterface $else = null, Twig_Node_Expression $joinedBy = null, $lineno, $tag = null)
    {
        parent::__construct(array('key_target' => $keyTarget, 'value_target' => $valueTarget, 'seq' => $seq, 'body' => $body, 'else' => $else, 'joined_with' => $joinedBy), array('with_loop' => true), $lineno, $tag);
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
            ->write('$context[\'_parent\'] = (array) $context;')
            ->write('$context[\'_seq\'] = twig_ensure_traversable(')
            ->subcompile($this->getNode('seq'))
            ->raw(');')
        ;

        if (null !== $this->getNode('else') || null !== $this->getNode('joined_with')) {
            $compiler->write('$context[\'_iterated\'] = false;');
        }

        if ($this->getAttribute('with_loop')) {
            $compiler
                ->write('$context[\'loop\'] = array(')
                ->write('  \'parent\' => $context[\'_parent\'],')
                ->write('  \'index0\' => 0,')
                ->write('  \'index\'  => 1,')
                ->write('  \'first\'  => true,')
                ->write(');')
                ->write('if (is_array($context[\'_seq\']) || (is_object($context[\'_seq\']) && $context[\'_seq\'] instanceof Countable)) {')
                ->indent()
                ->write('$length = count($context[\'_seq\']);')
                ->write('$context[\'loop\'][\'revindex0\'] = $length - 1;')
                ->write('$context[\'loop\'][\'revindex\'] = $length;')
                ->write('$context[\'loop\'][\'length\'] = $length;')
                ->write('$context[\'loop\'][\'last\'] = 1 === $length;')
                ->outdent()
                ->write('}')
            ;
        }

        $compiler
            ->write('foreach ($context[\'_seq\'] as ')
            ->subcompile($this->getNode('key_target'))
            ->raw(' => ')
            ->subcompile($this->getNode('value_target'))
            ->raw(') {')
            ->indent()
        ;

        if (null !== $this->getNode('joined_with')) {
            $compiler
                ->write('if ($context[\'_iterated\']) {')
                ->indent()
                ->write('echo ')
                ->subcompile($this->getNode('joined_with'))
                ->raw(';')
                ->outdent()
                ->write('}');
        }

        $compiler->subcompile($this->getNode('body'));

        if (null !== $this->getNode('else') || null !== $this->getNode('joined_with')) {
            $compiler->write('$context[\'_iterated\'] = true;');
        }

        if ($this->getAttribute('with_loop')) {
            $compiler
                ->write('++$context[\'loop\'][\'index0\'];')
                ->write('++$context[\'loop\'][\'index\'];')
                ->write('$context[\'loop\'][\'first\'] = false;')
                ->write('if (isset($context[\'loop\'][\'length\'])) {')
                ->indent()
                ->write('--$context[\'loop\'][\'revindex0\'];')
                ->write('--$context[\'loop\'][\'revindex\'];')
                ->write('$context[\'loop\'][\'last\'] = 0 === $context[\'loop\'][\'revindex0\'];')
                ->outdent()
                ->write('}')
            ;
        }

        $compiler
            ->outdent()
            ->write('}')
        ;

        if (null !== $this->getNode('else')) {
            $compiler
                ->write('if (!$context[\'_iterated\']) {')
                ->indent()
                ->subcompile($this->getNode('else'))
                ->outdent()
                ->write('}')
            ;
        }

        $compiler->write('$_parent = $context[\'_parent\'];');

        // remove some "private" loop variables (needed for nested loops)
        $compiler->write('unset($context[\'_parent\'], $context[\'_seq\'], $context[\'_iterated\'], $context[\'loop\'], $context[\''.$this->getNode('key_target')->getAttribute('name').'\'], $context[\''.$this->getNode('value_target')->getAttribute('name').'\']);');

        // keep the values set in the inner context for variables defined in the outer context
        $compiler->write('$context = array_merge($_parent, array_intersect_key($context, $_parent));');
    }
}
