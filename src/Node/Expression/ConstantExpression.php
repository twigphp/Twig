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

namespace Twig\Node\Expression;

use Twig\Compiler;

/**
 * Class ConstantExpression
 * @package Twig\Node\Expression
 */
class ConstantExpression extends AbstractExpression
{
    /**
     * ConstantExpression constructor.
     * @param $value
     * @param int $lineno
     */
    public function __construct($value, int $lineno)
    {
        parent::__construct([], ['value' => $value], $lineno);
    }

    /**
     * @param Compiler $compiler
     */
    public function compile(Compiler $compiler): void
    {
        $compiler->repr($this->getAttribute('value'));
    }
}
