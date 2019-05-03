<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Node\Expression;

use Twig\Compiler;

/**
 * Represents an arrow function.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class ArrowFunctionExpression extends AbstractExpression
{
    public function __construct(AbstractExpression $expr, array $names, $lineno, $tag = null)
    {
        parent::__construct(['expr' => $expr], ['names' => $names], $lineno, $tag);
    }

    public function compile(Compiler $compiler)
    {
        $compiler
            ->addDebugInfo($this)
            ->raw('function (')
        ;
        foreach ($this->getAttribute('names') as $i => $name) {
            if ($i) {
                $compiler->raw(', ');
            }

            $compiler->raw('$__'.$name.'__');
        }
        $compiler
            ->raw(') use ($context) { ')
        ;
        foreach ($this->getAttribute('names') as $name) {
            $compiler->raw('$context["'.$name.'"] = $__'.$name.'__; ');
        }
        $compiler
            ->raw('return ')
            ->subcompile($this->getNode('expr'))
            ->raw('; }')
        ;
    }
}
