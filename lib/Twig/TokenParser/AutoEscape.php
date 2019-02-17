<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Twig\Error\SyntaxError;
use Twig\Node\AutoEscapeNode;
use Twig\Node\Expression\ConstantExpression;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
 * Marks a section of a template to be escaped or not.
 */
final class Twig_TokenParser_AutoEscape extends AbstractTokenParser
{
    public function parse(Token $token)
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();

        if ($stream->test(/* Token::BLOCK_END_TYPE */ 3)) {
            $value = 'html';
        } else {
            $expr = $this->parser->getExpressionParser()->parseExpression();
            if (!$expr instanceof ConstantExpression) {
                throw new SyntaxError('An escaping strategy must be a string or false.', $stream->getCurrent()->getLine(), $stream->getSourceContext());
            }
            $value = $expr->getAttribute('value');
        }

        $stream->expect(/* Token::BLOCK_END_TYPE */ 3);
        $body = $this->parser->subparse([$this, 'decideBlockEnd'], true);
        $stream->expect(/* Token::BLOCK_END_TYPE */ 3);

        return new AutoEscapeNode($value, $body, $lineno, $this->getTag());
    }

    public function decideBlockEnd(Token $token)
    {
        return $token->test('endautoescape');
    }

    public function getTag()
    {
        return 'autoescape';
    }
}

class_alias('Twig_TokenParser_AutoEscape', 'Twig\TokenParser\AutoEscapeTokenParser', false);
