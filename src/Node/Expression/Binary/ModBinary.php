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

namespace Twig\Node\Expression\Binary;

use Twig\Compiler;

class ModBinary extends AbstractBinary
{
    public function compile(Compiler $compiler): void
    {
        $compiler
            ->raw('twig_mod(')
            ->subcompile($this->getNode('left'))
            ->raw(', ')
            ->subcompile($this->getNode('right'))
            ->raw(')')
        ;
    }
    
    public function operator(Compiler $compiler): Compiler
    {
        return $compiler->raw('%');
    }
}
