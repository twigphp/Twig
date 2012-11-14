<?php

/*
 * This file is part of Twig.
 *
 * (c) 2011 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Checks if a variable is numeric
 *
 * <pre>

 * {% if foo is numeric %}
 *     {# ... #}
 * {% endif %}
 * </pre>
 *
 * @package twig
 * @author  Fabien Potencier <fabien@symfony.com>
 */
class Twig_Node_Expression_Test_Numeric extends Twig_Node_Expression_Test
{
    public function compile(Twig_Compiler $compiler)
    {
        $compiler
            ->raw('(is_numeric(')
            ->subcompile($this->getNode('node'))
            ->raw('))')
        ;
    }
}
