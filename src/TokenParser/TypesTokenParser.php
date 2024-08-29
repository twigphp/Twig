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
use Twig\ExpressionParser;
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\NameExpression;
use Twig\Node\Node;
use Twig\Node\TypesNode;
use Twig\Token;
use Twig\TokenStream;

/**
 * Declare variable types.
 *
 *  {% types {foo: 'int', bar?: 'string'} %}
 *
 * @author Jeroen Versteeg <jeroen@alisqi.com>
 * @internal
 */
final class TypesTokenParser extends AbstractTokenParser
{
    public function parse(Token $token): Node
    {
        $stream = $this->parser->getStream();

        $expression = $this->parseSimpleMappingExpression($stream);

        $stream->expect(Token::BLOCK_END_TYPE);

        return new TypesNode($expression, $token->getLine());
    }

    /**
     * @throws SyntaxError
     * @see ExpressionParser::parseMappingExpression()
     */
    private function parseSimpleMappingExpression(TokenStream $stream): ArrayExpression
    {
        $stream->expect(Token::PUNCTUATION_TYPE, '{', 'A mapping element was expected');

        $node = new ArrayExpression([], $stream->getCurrent()->getLine());

        $first = true;
        while (!$stream->test(Token::PUNCTUATION_TYPE, '}')) {
            if (!$first) {
                $stream->expect(Token::PUNCTUATION_TYPE, ',', 'A type string must be followed by a comma');

                // trailing ,?
                if ($stream->test(Token::PUNCTUATION_TYPE, '}')) {
                    break;
                }
            }
            $first = false;

            $nameToken = $stream->expect(Token::NAME_TYPE);
            $nameExpression = new NameExpression($nameToken->getValue(), $nameToken->getLine());

            $isOptional = $stream->nextIf(Token::PUNCTUATION_TYPE, '?') !== null;
            $nameExpression->setAttribute('is_optional', $isOptional);

            $stream->expect(Token::PUNCTUATION_TYPE, ':', 'A name must be followed by a colon (:)');

            $valueToken = $stream->expect(Token::STRING_TYPE);
            $valueExpression = new ConstantExpression($valueToken->getValue(), $valueToken->getLine());

            $node->addElement($valueExpression, $nameExpression);
        }
        $stream->expect(Token::PUNCTUATION_TYPE, '}', 'An opened mapping is not properly closed');

        return $node;
    }

    public function getTag(): string
    {
        return 'types';
    }
}
