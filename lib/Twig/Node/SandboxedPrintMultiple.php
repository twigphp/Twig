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
 * Twig_Node_SandboxedPrintMultiple adds a check for the __toString() method
 * when the variable is an object and the sandbox is activated.
 *
 * When there is a simple Print statement, like {{ article }},
 * and if the sandbox is enabled, we need to check that the __toString()
 * method is allowed if 'article' is an object.
 *
 * @package    twig
 * @author     Vladimir Beloborodov <redhead.ru@gmail.com>
 */
class Twig_Node_SandboxedPrintMultiple extends Twig_Node_PrintMultiple
{
    public function __construct(Twig_Node_PrintMultiple $node, $tag = null)
    {
        parent::__construct($node->nodes, $tag);
    }

    protected function compileExpr($compiler,$node) {
        $compiler
            ->raw('$this->env->getExtension(\'sandbox\')->ensureToStringAllowed(')
            ->subcompile($node)
            ->raw(')')
            ;
    }
}
