<?php

/*
 * This file is part of Twig.
 *
 * (c) 2010 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Represents a debug node.
 *
 * @package    twig
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
class Twig_Node_Debug extends Twig_Node
{
    public function __construct(Twig_Node_Expression $expr = null, $lineno, $tag = null)
    {
        parent::__construct(array('expr' => $expr), array(), $lineno, $tag);
    }

    /**
     * Compiles the node to PHP.
     *
     * @param Twig_Compiler A Twig_Compiler instance
     */
    public function compile($compiler)
    {
        $compiler->addDebugInfo($this);

        $compiler
            ->write("if (\$this->env->isDebug()) {\n")
            ->indent()
        ;

        if (null === $this->expr) {
            // remove embedded templates (macros) from the context
            $compiler
                ->write("\$vars = array();\n")
                ->write("foreach (\$context as \$key => \$value) {\n")
                ->indent()
                ->write("if (!\$value instanceof Twig_Template) {\n")
                ->indent()
                ->write("\$vars[\$key] = \$value;\n")
                ->outdent()
                ->write("}\n")
                ->outdent()
                ->write("}\n")
                ->write("print_r(\$vars);\n")
            ;
        } else {
            $compiler
                ->write("print_r(")
                ->subcompile($this->expr)
                ->raw(");\n")
            ;
        }

        $compiler
            ->outdent()
            ->write("}\n")
        ;
    }
}
