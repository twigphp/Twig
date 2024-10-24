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

final class AssignTemplateVariable extends TemplateVariable
{
    public function __construct(string|int|null $name, int $lineno, bool $global = true)
    {
        parent::__construct($name, $lineno);

        $this->setAttribute('global', $global);
    }

    public function compile(Compiler $compiler): void
    {
        if (null === $this->getAttribute('name')) {
            $this->setAttribute('name', \sprintf('_l%d', $compiler->getVarName()));
        }

        $compiler
            ->addDebugInfo($this)
            ->write('$macros[')
            ->string($this->getAttribute('name'))
            ->raw('] = ')
        ;

        if ($this->getAttribute('global')) {
            $compiler
                ->raw('$this->macros[')
                ->string($this->getAttribute('name'))
                ->raw('] = ')
            ;
        }
    }
}
