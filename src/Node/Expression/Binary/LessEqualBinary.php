<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Node\Expression\Binary;

use Twig\Compiler;

/**
 * Class LessEqualBinary
 * @package Twig\Node\Expression\Binary
 */
class LessEqualBinary extends AbstractBinary
{
    /**
     * @param Compiler $compiler
     */
    public function compile(Compiler $compiler): void
    {
        if (\PHP_VERSION_ID >= 80000) {
            parent::compile($compiler);

            return;
        }

        $compiler
            ->raw('(0 >= twig_compare(')
            ->subcompile($this->getNode('left'))
            ->raw(', ')
            ->subcompile($this->getNode('right'))
            ->raw('))')
        ;
    }

    /**
     * @param Compiler $compiler
     * @return Compiler
     */
    public function operator(Compiler $compiler): Compiler
    {
        return $compiler->raw('<=');
    }
}
