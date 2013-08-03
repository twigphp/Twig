<?php

/*
 * This file is part of Twig.
 *
 * (c) 2012 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Represents a macro call node.
 *
 * @author Martin Hasoň <martin.hason@gmail.com>
 */
class Twig_Node_Expression_MacroCall extends Twig_Node_Expression
{
    public function __construct(Twig_Node_Expression $template, $name, Twig_Node_Expression_Array $arguments, $lineno)
    {
        parent::__construct(array('template' => $template, 'arguments' => $arguments), array('name' => $name), $lineno);
    }

    public function compile(Twig_Compiler $compiler)
    {
        $namedNames = array();
        $namedCount = 0;
        $positionalCount = 0;
        foreach ($this->getNode('arguments')->getKeyValuePairs() as $pair) {
            $name = $pair['key']->getAttribute('value');
            if (!is_int($name)) {
                $namedCount++;
                $namedNames[$name] = 1;
            } elseif ($namedCount > 0) {
                throw new Twig_Error_Syntax(sprintf('Positional arguments cannot be used after named arguments for macro "%s".', $this->getAttribute('name')), $this->lineno);
            } else {
                $positionalCount++;
            }
        }

        $compiler
            ->raw('$this->callMacro(')
            ->subcompile($this->getNode('template'))
            ->raw(', ')->repr($this->getAttribute('name'))
            ->raw(', ')->subcompile($this->getNode('arguments'))
        ;

        if ($namedCount > 0) {
            $compiler
                ->raw(', ')->repr($namedNames)
                ->raw(', ')->repr($namedCount)
                ->raw(', ')->repr($positionalCount)
            ;
        }

        $compiler
            ->raw(')')
        ;
    }
}
