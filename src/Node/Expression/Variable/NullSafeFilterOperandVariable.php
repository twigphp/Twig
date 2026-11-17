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
use Twig\Node\Expression\AbstractExpression;

/**
 * Holds the operand of a null-safe filter (a ?| filter) once it has been checked against null.
 *
 * The compiled variable name is stored in the "operand" attribute, an object
 * shared with the enclosing NullSafeFilterExpression. As cloning a node only
 * copies its attributes by reference, all clones of this node read the name
 * set by the NullSafeFilterExpression being compiled.
 *
 * @internal
 */
final class NullSafeFilterOperandVariable extends AbstractExpression
{
    public function __construct(int $lineno)
    {
        parent::__construct([], ['operand' => new \stdClass()], $lineno);
    }

    public function compile(Compiler $compiler): void
    {
        if (null === $name = $this->getAttribute('operand')->name ?? null) {
            throw new \LogicException(\sprintf('A "%s" node can only be compiled inside its "NullSafeFilterExpression" node.', self::class));
        }

        $compiler->raw('$'.$name);
    }
}
