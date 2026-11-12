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
use Twig\Node\Node;

/**
 * Represents a null-safe filter call (a ?| filter).
 *
 * The operand is evaluated once; when it is null, the filter is not called
 * and the expression evaluates to null.
 */
final class NullSafeFilterExpression extends AbstractExpression implements OperatorEscapeInterface
{
    /**
     * @param AbstractExpression $filter A filter expression whose input is a NullSafeFilterOperandVariable
     */
    public function __construct(AbstractExpression $node, AbstractExpression $filter, int $lineno)
    {
        parent::__construct(['node' => $node, 'filter' => $filter], [], $lineno);
    }

    public function compile(Compiler $compiler): void
    {
        $name = $compiler->getVarName();

        // the operand variable is looked up at compile time as node visitors
        // may have wrapped it and the whole expression may have been cloned
        if (null === $operand = $this->findOperandVariable($this->getNode('filter'))) {
            throw new \LogicException(\sprintf('The "filter" node of a "%s" node must contain a "%s" node.', self::class, NullSafeFilterOperandVariable::class));
        }
        $operand->setAttribute('name', $name);

        $compiler
            ->raw('((null === ($'.$name.' = ')
            ->subcompile($this->getNode('node'))
            ->raw(')) ? null : (')
            ->subcompile($this->getNode('filter'))
            ->raw('))')
        ;
    }

    public function getOperandNamesToEscape(): array
    {
        // null does not need to be escaped
        return ['filter'];
    }

    private function findOperandVariable(Node $node): ?NullSafeFilterOperandVariable
    {
        if ($node instanceof NullSafeFilterOperandVariable) {
            return $node;
        }

        foreach ($node as $child) {
            // a nested null-safe filter has its own operand variable
            if ($child instanceof self) {
                continue;
            }

            if (null !== $operand = $this->findOperandVariable($child)) {
                return $operand;
            }
        }

        return null;
    }
}
