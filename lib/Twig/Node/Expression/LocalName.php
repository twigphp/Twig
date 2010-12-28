<?php

/*
 * This file is part of Twig.
 *
 * (c) 2009 Fabien Potencier
 * (c) 2010 Arnaud Le Blanc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Represents a local private variable.
 *
 * Such variables are not visible from templates.
 *
 * @package    twig
 * @author     Arnaud Le Blanc <arnaud.lb@gmail.com>
 */
class Twig_Node_Expression_LocalName extends Twig_Node_Expression
{
    static protected $counter = 0;

    public function __construct($name = null, $lineno = null)
    {
        if (null === $name) {
            $uniq = self::$counter++;
            $name = '__'.$uniq;
        }

        parent::__construct(array(), array('name' => $name), $lineno);
    }

    public function compile($compiler)
    {
        $compiler->raw('$'.$this->getAttribute('name'));
    }
}

