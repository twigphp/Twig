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

final class GlobalTemplateVariable extends TemplateVariable
{
    public function compile(Compiler $compiler): void
    {
        if (null === $this->getAttribute('name')) {
            $this->setAttribute('name', \sprintf('_l%d', $compiler->getVarName()));
        }

        if ('_self' === $this->getAttribute('name')) {
            $compiler->raw('$this');
        } else {
            $compiler
                ->raw('$this->macros[')
                ->string($this->getAttribute('name'))
                ->raw(']')
            ;
        }
    }
}
