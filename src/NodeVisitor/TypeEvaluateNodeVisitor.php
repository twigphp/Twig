<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\NodeVisitor;

use Twig\Environment;
use Twig\Node\AutoEscapeNode;
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Expression\AssignNameExpression;
use Twig\Node\Expression\Binary\AddBinary;
use Twig\Node\Expression\Binary\AndBinary;
use Twig\Node\Expression\Binary\BitwiseAndBinary;
use Twig\Node\Expression\Binary\BitwiseOrBinary;
use Twig\Node\Expression\Binary\BitwiseXorBinary;
use Twig\Node\Expression\Binary\ConcatBinary;
use Twig\Node\Expression\Binary\DivBinary;
use Twig\Node\Expression\Binary\EndsWithBinary;
use Twig\Node\Expression\Binary\EqualBinary;
use Twig\Node\Expression\Binary\FloorDivBinary;
use Twig\Node\Expression\Binary\GreaterBinary;
use Twig\Node\Expression\Binary\GreaterEqualBinary;
use Twig\Node\Expression\Binary\HasEveryBinary;
use Twig\Node\Expression\Binary\HasSomeBinary;
use Twig\Node\Expression\Binary\InBinary;
use Twig\Node\Expression\Binary\LessBinary;
use Twig\Node\Expression\Binary\LessEqualBinary;
use Twig\Node\Expression\Binary\MatchesBinary;
use Twig\Node\Expression\Binary\ModBinary;
use Twig\Node\Expression\Binary\MulBinary;
use Twig\Node\Expression\Binary\NotEqualBinary;
use Twig\Node\Expression\Binary\NotInBinary;
use Twig\Node\Expression\Binary\OrBinary;
use Twig\Node\Expression\Binary\PowerBinary;
use Twig\Node\Expression\Binary\RangeBinary;
use Twig\Node\Expression\Binary\SpaceshipBinary;
use Twig\Node\Expression\Binary\StartsWithBinary;
use Twig\Node\Expression\Binary\SubBinary;
use Twig\Node\Expression\BlockReferenceExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\ParentExpression;
use Twig\Node\Expression\TestExpression;
use Twig\Node\Expression\Unary\NegUnary;
use Twig\Node\Expression\Unary\NotUnary;
use Twig\Node\Expression\Unary\PosUnary;
use Twig\Node\MacroNode;
use Twig\Node\Node;
use Twig\Node\SetNode;
use Twig\TypeHint\ArrayType;
use Twig\TypeHint\TypeFactory;
use Twig\TypeHint\TypeInterface;
use Twig\TypeHint\UnionType;

/**
 * Tries to evaluate types for optimized attribute access.
 *
 * This visitor should be used late in priority.
 *
 * @author Joshua Behrens <code@joshua-behrens.de>
 *
 * @internal
 */
final class TypeEvaluateNodeVisitor implements NodeVisitorInterface
{
    public function enterNode(Node $node, Environment $env): Node
    {
        return $node;
    }

    public function leaveNode(Node $node, Environment $env): ?Node
    {
        $possibleTypes = [];

        foreach ($this->getPossibleTypes($node) as $possibleType) {
            if (!$possibleType instanceof TypeInterface) {
                $possibleType = TypeFactory::createTypeFromText((string) $possibleType);
            }

            $possibleTypes[] = $possibleType;
        }

        if (\count($possibleTypes) !== 1) {
            $node->setAttribute('typeHint', new UnionType($possibleTypes));
        } elseif ($possibleTypes !== []) {
            $node->setAttribute('typeHint', $possibleTypes[0]);
        }

        if ($node instanceof SetNode) {
            /** @var array<string, TypeInterface> $typedVariables */
            $typedVariables = [];

            // capture is always string
            if ($node->getAttribute('capture')) {
                $stringType = TypeFactory::createTypeFromText('string');
                /** @var Node $innerNameNode */
                foreach ($node->getNode('names') as $innerNameNode) {
                    $typedVariables[$innerNameNode->getAttribute('name')] = $stringType;
                }
            } else {
                // TODO push state
                /** @var AssignNameExpression $innerNameNode */
                foreach ($node->getNode('names') as $nameIndex => $innerNameNode) {
                    $typedVariables[$innerNameNode->getAttribute('name')] = $node->getNode('values')->getNode($nameIndex)->getAttribute('typeHint');
                }
            }

            if ($typedVariables !== []) {
                $node->setAttribute('typeHint', new ArrayType($typedVariables));
            }
        }

        if ($node instanceof ArrayExpression) {
            $typedVariables = [];

            for ($arrayIterator = $node->count() - 2; $arrayIterator >= 0; $arrayIterator -= 2) {
                $nameNode = $node->getNode($arrayIterator);
                $valueNode = $node->getNode($arrayIterator + 1);

                if ($nameNode instanceof ConstantExpression) {
                    $varName = $nameNode->getAttribute('value');

                    if ($valueNode->hasAttribute('typeHint')) {
                        $typedVariables[$varName] = $valueNode->getAttribute('typeHint');
                    }
                }
            }

            if ($typedVariables !== []) {
                $node->setAttribute('typeHint', new ArrayType($typedVariables));
            }
        }

        return $node;
    }

    public function getPriority(): int
    {
        return 10;
    }

    private function getPossibleTypes(Node $node): iterable
    {
        if ($node instanceof AutoEscapeNode) {
            yield 'string';
        }

        if ($node instanceof ConstantExpression) {
            yield from $this->getPossibleConstantExpressionTypes($node);
        }

        if ($node instanceof MacroNode) {
            yield 'string';
        }

        if ($node instanceof ArrayExpression) { // VariadicExpression
            yield 'array';
            yield '\\ArrayAccess';
        }

        if ($node instanceof BlockReferenceExpression) {
            yield 'string';
        }

        if ($node instanceof ParentExpression) {
            yield 'string';
        }

        if ($node instanceof TestExpression) {
            yield 'boolean';
        }

        if ($node instanceof NotUnary) {
            yield 'boolean';
        }

        if ($node instanceof NegUnary) {
            yield 'integer';
            yield 'float';
        }

        if ($node instanceof PosUnary) {
            yield 'integer';
            yield 'float';
        }

        yield from $this->getPossibleTypesOfBinaryExpression($node);

        if (\get_class($node) === Node::class) {
            /** @var Node $innerNode */
            foreach ($node as $innerNode) {
                if (!$innerNode->hasAttribute('typeHint')) {
                    continue;
                }

                yield $innerNode->getAttribute('typeHint');
            }
        }
    }

    /**
     * @return iterable<string>
     */
    private function getPossibleConstantExpressionTypes(ConstantExpression $node): iterable
    {
        $nodeValue = $node->getAttribute('value');

        if (!\is_object($nodeValue)) {
            $phpType = \gettype($nodeValue);

            if ($phpType === 'double') {
                yield 'float';
            } elseif ($phpType === 'NULL') {
                yield 'float';
            } else {
                yield $phpType;
            }
        } else {
            yield '\\' . \get_class($nodeValue);
        }
    }

    /**
     * @return iterable<string>
     */
    private function getPossibleTypesOfBinaryExpression(Node $node): iterable
    {
        if ($node instanceof AddBinary) {
            yield 'integer';
            yield 'float';
        }

        if ($node instanceof AndBinary) {
            yield 'boolean';
        }

        if ($node instanceof BitwiseAndBinary) {
            yield 'integer';
        }

        if ($node instanceof BitwiseOrBinary) {
            yield 'integer';
        }

        if ($node instanceof BitwiseXorBinary) {
            yield 'integer';
        }

        if ($node instanceof ConcatBinary) {
            yield 'string';
        }

        if ($node instanceof DivBinary) {
            yield 'integer';
            yield 'float';
        }

        if ($node instanceof EndsWithBinary) {
            yield 'boolean';
        }

        if ($node instanceof EqualBinary) {
            yield 'boolean';
        }

        if ($node instanceof FloorDivBinary) {
            yield 'integer';
        }

        if ($node instanceof GreaterBinary) {
            yield 'boolean';
        }

        if ($node instanceof GreaterEqualBinary) {
            yield 'boolean';
        }

        if ($node instanceof HasEveryBinary) {
            yield 'boolean';
        }

        if ($node instanceof HasSomeBinary) {
            yield 'boolean';
        }

        if ($node instanceof InBinary) {
            yield 'boolean';
        }

        if ($node instanceof LessBinary) {
            yield 'boolean';
        }

        if ($node instanceof LessEqualBinary) {
            yield 'boolean';
        }

        if ($node instanceof MatchesBinary) {
            yield 'boolean';
        }

        if ($node instanceof ModBinary) {
            yield 'integer';
        }

        if ($node instanceof MulBinary) {
            yield 'integer';
            yield 'float';
        }

        if ($node instanceof NotEqualBinary) {
            yield 'boolean';
        }

        if ($node instanceof NotInBinary) {
            yield 'boolean';
        }

        if ($node instanceof OrBinary) {
            yield 'boolean';
        }

        if ($node instanceof PowerBinary) {
            yield 'integer';
            yield 'float';
        }

        if ($node instanceof RangeBinary) {
            yield 'array';
            yield '\\ArrayAccess';
        }

        if ($node instanceof SpaceshipBinary) {
            yield 'integer';
        }

        if ($node instanceof StartsWithBinary) {
            yield 'boolean';
        }

        if ($node instanceof SubBinary) {
            yield 'integer';
            yield 'float';
        }
    }
}
