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
use Twig\Node\Expression\AbstractExpression;
use Twig\Node\Node;

/**
 * Class AbstractBinary
 * @package Twig\Node\Expression\Binary
 */
abstract class AbstractBinary extends AbstractExpression
{
    /**
     * AbstractBinary constructor.
     * @param Node $left
     * @param Node $right
     * @param int $lineno
     */
    public function __construct(Node $left, Node $right, int $lineno)
    {
        parent::__construct(['left' => $left, 'right' => $right], [], $lineno);
    }

    /**
     * @param Compiler $compiler
     */
    public function compile(Compiler $compiler): void
    {
        $compiler
            ->raw('(')
            ->subcompile($this->getNode('left'))
            ->raw(' ')
        ;
        $this->operator($compiler);
        $compiler
            ->raw(' ')
            ->subcompile($this->getNode('right'))
            ->raw(')')
        ;
    }

    /**
     * @param Compiler $compiler
     * @return Compiler
     */
    abstract public function operator(Compiler $compiler): Compiler;
}
