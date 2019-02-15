<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Creates a nested scope.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
final class Twig_TokenParser_With extends \Twig\TokenParser\AbstractTokenParser
{
    public function parse(\Twig\Token $token)
    {
        $stream = $this->parser->getStream();

        $variables = null;
        $only = false;
        if (!$stream->test(/* \Twig\Token::BLOCK_END_TYPE */ 3)) {
            $variables = $this->parser->getExpressionParser()->parseExpression();
            $only = $stream->nextIf(/* \Twig\Token::NAME_TYPE */ 5, 'only');
        }

        $stream->expect(/* \Twig\Token::BLOCK_END_TYPE */ 3);

        $body = $this->parser->subparse([$this, 'decideWithEnd'], true);

        $stream->expect(/* \Twig\Token::BLOCK_END_TYPE */ 3);

        return new \Twig\Node\WithNode($body, $variables, $only, $token->getLine(), $this->getTag());
    }

    public function decideWithEnd(\Twig\Token $token)
    {
        return $token->test('endwith');
    }

    public function getTag()
    {
        return 'with';
    }
}

class_alias('Twig_TokenParser_With', 'Twig\TokenParser\WithTokenParser', false);
