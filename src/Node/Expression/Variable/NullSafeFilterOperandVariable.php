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

use Twig\Node\Expression\TempNameExpression;

/**
 * Holds the operand of a null-safe filter (a ?| filter) once it has been checked against null.
 *
 * Its name is set by the enclosing NullSafeFilterExpression when compiled.
 *
 * @internal
 */
final class NullSafeFilterOperandVariable extends TempNameExpression
{
    public function __construct(int $lineno)
    {
        parent::__construct(null, $lineno);
    }
}
