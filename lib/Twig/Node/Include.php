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
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
class Twig_Node_Include extends Twig_Node
{
    public function __construct(Twig_Node_Expression $expr, Twig_Node_Expression $variables = null, $lineno, $tag = null)
    {
        parent::__construct(array('expr' => $expr, 'variables' => $variables), array(), $lineno, $tag);
    }

    public function compile($compiler)
    {
        $compiler
            ->addDebugInfo($this)
            ->write('$this->env->loadTemplate(')
            ->subcompile($this->expr)
            ->raw(')->display(')
        ;

        if (null === $this->variables) {
            $compiler->raw('$context');
        } else {
            $compiler->subcompile($this->variables);
        }

        $compiler->raw(");\n");
    }
}
