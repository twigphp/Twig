<?php

/*
 * This file is part of Twig.
 *
 * (c) 2016 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Checks whether a block is defined.
 *
 * <pre>
 *  {% if 'title' is block name %}
 *    <title>{{ block('title') }}</title>
 *  {% endif %}
 * </pre>
 *
 * @author Martin Hasoň <martin.hason@gmail.com>
 */
class Twig_Node_Expression_Test_BlockName extends Twig_Node_Expression_Test
{
    public function compile(Twig_Compiler $compiler)
    {
        $compiler
            ->raw('$this->blockExists(')
            ->subcompile($this->getNode('node'))
            ->raw(', $context, $blocks)')
        ;
    }
}
