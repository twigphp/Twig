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
 * Twig_Node_SandboxedPrint adds a check for the __toString() method
 * when the variable is an object and the sandbox is activated.
 *
 * When there is a simple Print statement, like {{ article }},
 * and if the sandbox is enabled, we need to check that the __toString()
 * method is allowed if 'article' is an object.
 *
 * @package    twig
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
class Twig_Node_SandboxedPrint extends Twig_Node_Print
{
    public function __construct(Twig_Node_Print $node)
    {
        parent::__construct($node->expr, $node->getLine(), $node->getNodeTag());
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
            ->write('if ($this->env->hasExtension(\'sandbox\') && is_object(')
            ->subcompile($this->expr)
            ->raw(')) {'."\n")
            ->indent()
            ->write('$this->env->getExtension(\'sandbox\')->checkMethodAllowed(')
            ->subcompile($this->expr)
            ->raw(', \'__toString\');'."\n")
            ->outdent()
            ->write('}'."\n")
        ;

        parent::compile($compiler);
    }
}
