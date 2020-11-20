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

namespace Twig\Node\Expression\Unary;

use Twig\Compiler;
use Twig\Node\Expression\AbstractExpression;
use Twig\Node\Node;

/**
 * Class AbstractUnary
 * @package Twig\Node\Expression\Unary
 */
abstract class AbstractUnary extends AbstractExpression
{
    /**
     * AbstractUnary constructor.
     * @param Node $node
     * @param int $lineno
     */
    public function __construct(Node $node, int $lineno)
    {
        parent::__construct(['node' => $node], [], $lineno);
    }

    /**
     * @param Compiler $compiler
     */
    public function compile(Compiler $compiler): void
    {
        $compiler->raw(' ');
        $this->operator($compiler);
        $compiler->subcompile($this->getNode('node'));
    }

    /**
     * @param Compiler $compiler
     * @return Compiler
     */
    abstract public function operator(Compiler $compiler): Compiler;
}
