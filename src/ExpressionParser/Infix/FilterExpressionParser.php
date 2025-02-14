<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\ExpressionParser\Infix;

use Twig\ExpressionParser\AbstractExpressionParser;
use Twig\ExpressionParser\ExpressionParserDescriptionInterface;
use Twig\ExpressionParser\InfixAssociativity;
use Twig\ExpressionParser\InfixExpressionParserInterface;
use Twig\Node\EmptyNode;
use Twig\Node\Expression\AbstractExpression;
use Twig\Parser;
use Twig\Token;

/**
 * @internal
 */
final class FilterExpressionParser extends AbstractExpressionParser implements InfixExpressionParserInterface, ExpressionParserDescriptionInterface
{
    use ArgumentsTrait;

    public function parse(Parser $parser, AbstractExpression $expr, Token $token): AbstractExpression
    {
        $stream = $parser->getStream();
        $token = $stream->expect(Token::NAME_TYPE);
        $line = $token->getLine();

        if (!$stream->test(Token::OPERATOR_TYPE, '(')) {
            $arguments = new EmptyNode();
        } else {
            $arguments = $this->parseNamedArguments($parser);
        }

        $filter = $parser->getFilter($token->getValue(), $line);

        return new ($filter->getNodeClass())($expr, $filter, $arguments, $line);
    }

    public function getName(): string
    {
        return '|';
    }

    public function getDescription(): string
    {
        return 'Twig filter call';
    }

    public function getPrecedence(): int
    {
        return 300;
    }

    public function getAssociativity(): InfixAssociativity
    {
        return InfixAssociativity::Left;
    }
}
