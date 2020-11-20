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
 * Class FloorDivBinary
 * @package Twig\Node\Expression\Binary
 */
class FloorDivBinary extends AbstractBinary
{
    /**
     * @param Compiler $compiler
     */
    public function compile(Compiler $compiler): void
    {
        $compiler->raw('(int) floor(');
        parent::compile($compiler);
        $compiler->raw(')');
    }

    /**
     * @param Compiler $compiler
     * @return Compiler
     */
    public function operator(Compiler $compiler): Compiler
    {
        return $compiler->raw('/');
    }
}
