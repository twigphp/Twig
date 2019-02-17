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
use Twig\Node\Expression\Binary\AbstractBinary;

class Twig_Node_Expression_Binary_Div extends AbstractBinary
{
    public function operator(Compiler $compiler)
    {
        return $compiler->raw('/');
    }
}

class_alias('Twig_Node_Expression_Binary_Div', 'Twig\Node\Expression\Binary\DivBinary', false);
