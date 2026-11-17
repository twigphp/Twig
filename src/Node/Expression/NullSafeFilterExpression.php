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
use Twig\Node\Expression\Variable\NullSafeFilterOperandVariable;

/**
 * Represents a null-safe filter call (a ?| filter).
 *
 * The operand is evaluated once; when it is null, the filter is not called
 * and the expression evaluates to null. Otherwise, the filter is called with
 * the operand variable as its input.
 */
final class NullSafeFilterExpression extends AbstractExpression implements OperatorEscapeInterface
{
    /**
     * @param AbstractExpression $filter A filter expression that uses $operand as its input
     */
    public function __construct(AbstractExpression $node, AbstractExpression $filter, NullSafeFilterOperandVariable $operand, int $lineno)
    {
        parent::__construct(['node' => $node, 'filter' => $filter], ['operand' => $operand->getAttribute('operand')], $lineno);
    }

    public function isOperandVariable(NullSafeFilterOperandVariable $node): bool
    {
        return $node->getAttribute('operand') === $this->getAttribute('operand');
    }

    public function compile(Compiler $compiler): void
    {
        $name = $compiler->getVarName();

        $compiler
            ->raw('((null === ($'.$name.' = ')
            ->subcompile($this->getNode('node'))
            ->raw(')) ? null : (')
        ;

        $operand = $this->getAttribute('operand');
        $previous = $operand->name ?? null;
        $operand->name = $name;
        try {
            $compiler->subcompile($this->getNode('filter'));
        } finally {
            $operand->name = $previous;
        }

        $compiler->raw('))');
    }

    public function getOperandNamesToEscape(): array
    {
        return ['filter'];
    }
}
