<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 * (c) Armin Ronacher
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Twig\Compiler;
use Twig\Node\Expression\Unary\AbstractUnary;

class Twig_Node_Expression_Unary_Pos extends AbstractUnary
{
    public function operator(Compiler $compiler)
    {
        $compiler->raw('+');
    }
}

class_alias('Twig_Node_Expression_Unary_Pos', 'Twig\Node\Expression\Unary\PosUnary', false);
