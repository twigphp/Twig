<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Node\Expression\Variable;

use Twig\Compiler;
use Twig\Node\Expression\NameExpression;

final class TemplateVariable extends NameExpression
{
    public function compile(Compiler $compiler): void
    {
        if ('_self' === $this->getAttribute('name')) {
            $compiler->raw('$this');
        } else {
            $compiler
                ->raw('$macros[')
                ->string($this->getAttribute('name'))
                ->raw(']')
            ;
        }
    }
}
