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

use Twig\Node\AssertNode;
use Twig\Node\Node;
use Twig\Token;

/**
 * Evaluates an expression and triggers an error if the result is false.
 *
 * @author Simon André<smn.andre@gmail.com>
 *
 * @internal
 */
final class AssertTokenParser extends AbstractTokenParser
{
    public function parse(Token $token): Node
    {
        $expr = $this->parser->getExpressionParser()->parseExpression();

        $this->parser->getStream()->expect(Token::BLOCK_END_TYPE);

        return new AssertNode($expr, $token->getLine(), $this->getTag());
    }

    public function getTag(): string
    {
        return 'assert';
    }
}
