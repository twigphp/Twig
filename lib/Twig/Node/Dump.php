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
 * Represents a set node.
 *
 * @package    twig
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
class Twig_Node_Dump extends Twig_Node
{
    public function __construct($value, $lineno, $tag = null)
    {
        parent::__construct(array('value' => $value), array(), $lineno, $tag);
    }

    /**
     * Compiles the node to PHP.
     *
     * @param Twig_Compiler A Twig_Compiler instance
     */
    public function compile($compiler)
    {
        $compiler->addDebugInfo($this);

        $value = $this->getNode('value');

        $compiler->write('var_dump(');
        if ($value) {
            $compiler->subcompile($value);
        } else {
            $compiler->raw('$context');
        }
        $compiler->raw(");\n");
        $compiler->raw(";\n");
    }
}
