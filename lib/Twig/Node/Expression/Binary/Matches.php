<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Twig_Node_Expression_Binary_Matches extends \Twig\Node\Expression\Binary\AbstractBinary
{
    public function compile(\Twig\Compiler $compiler)
    {
        $compiler
            ->raw('preg_match(')
            ->subcompile($this->getNode('right'))
            ->raw(', ')
            ->subcompile($this->getNode('left'))
            ->raw(')')
        ;
    }

    public function operator(\Twig\Compiler $compiler)
    {
        return $compiler->raw('');
    }
}

class_alias('Twig_Node_Expression_Binary_Matches', 'Twig\Node\Expression\Binary\MatchesBinary', false);
