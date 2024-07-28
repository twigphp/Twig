<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Node\Expression\Filter;

use Twig\Compiler;
use Twig\Node\Expression\FilterExpression;

/**
 * @author Fabien Potencier <fabien@symfony.com>
 */
class RawFilter extends FilterExpression
{
    public function compile(Compiler $compiler): void
    {
        $compiler->subcompile($this->getNode('node'));
    }
}
