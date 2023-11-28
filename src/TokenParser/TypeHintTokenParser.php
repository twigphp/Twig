<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\TokenParser;

use Twig\Error\SyntaxError;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\NameExpression;
use Twig\Node\Node;
use Twig\Node\TypeHintNode;
use Twig\Token;

/**
 * Keeps track of variable types for accessor generation.
 *
 *     {% type interval \DateInterval|null %}
 *
 * @author Joshua Behrens <code@joshua-behrens.de>
 * @internal
 */
final class TypeHintTokenParser extends AbstractTokenParser
{
    public function parse(Token $token): Node
    {
        $stream = $this->parser->getStream();
        $name = $this->parser->getExpressionParser()->parseExpression();

        if (!$name instanceof NameExpression) {
            throw new SyntaxError('A type hint must refer to a variable with a constant name', $stream->getCurrent()->getLine(), $stream->getSourceContext());
        }

        $type = $this->parser->getExpressionParser()->parseExpression();
        $typeValue = null;

        if ($type instanceof NameExpression) {
            $typeValue = $type->getAttribute('name');
        }

        if ($type instanceof ConstantExpression) {
            $typeValue = $type->getAttribute('value');
        }

        if (!\is_string($typeValue)) {
            throw new SyntaxError('A type hint must refer to a type with a constant name', $stream->getCurrent()->getLine(), $stream->getSourceContext());
        }

        $this->parser->getStream()->expect(/* Token::BLOCK_END_TYPE */ 3);

        return new TypeHintNode(
            $name->getAttribute('name'),
            $typeValue,
            $token->getLine(),
            $this->getTag()
        );
    }

    public function getTag(): string
    {
        return 'type';
    }
}
