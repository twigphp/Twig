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
 * Represents an include node.
 *
 * @package    twig
 * @author     Fabien Potencier <fabien@symfony.com>
 */
class Twig_Node_Include extends Twig_Node implements Twig_NodeOutputInterface
{
    public function __construct(Twig_Node_Expression $expr, Twig_Node_Expression $variables = null, $only = false, $lineno, $tag = null)
    {
        parent::__construct(array('expr' => $expr, 'variables' => $variables), array('only' => (Boolean) $only), $lineno, $tag);
    }

    /**
     * Compiles the node to PHP.
     *
     * @param Twig_Compiler A Twig_Compiler instance
     */
    public function compile(Twig_Compiler $compiler)
    {
        $compiler->addDebugInfo($this);

        if ($this->getNode('expr') instanceof Twig_Node_Expression_Constant) {
            $compiler
                ->write("\$this->env->loadTemplate(")
                ->subcompile($this->getNode('expr'))
                ->raw(")->display(")
            ;
        } else {
            $compiler
                ->write("\$template = ")
                ->subcompile($this->getNode('expr'))
                ->raw(";\n")
                ->write("if (!\$template")
                ->raw(" instanceof Twig_Template) {\n")
                ->indent()
                ->write("\$template = \$this->env->loadTemplate(\$template);\n")
                ->outdent()
                ->write("}\n")
                ->write('$template->display(')
            ;
        }

        if (false === $this->getAttribute('only')) {
            if (null === $this->getNode('variables')) {
                $compiler->raw('$context');
            } else {
                $compiler
                    ->raw('array_merge($context, ')
                    ->subcompile($this->getNode('variables'))
                    ->raw(')')
                ;
            }
        } else {
            if (null === $this->getNode('variables')) {
                $compiler->raw('array()');
            } else {
                $compiler->subcompile($this->getNode('variables'));
            }
        }

        $compiler->raw(");\n");
    }
}
