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
 * Flushes the output to the client.
 *
 * @see flush()
 */
final class Twig_TokenParser_Flush extends \Twig\TokenParser\AbstractTokenParser
{
    public function parse(\Twig\Token $token)
    {
        $this->parser->getStream()->expect(/* \Twig\Token::BLOCK_END_TYPE */ 3);

        return new \Twig\Node\FlushNode($token->getLine(), $this->getTag());
    }

    public function getTag()
    {
        return 'flush';
    }
}

class_alias('Twig_TokenParser_Flush', 'Twig\TokenParser\FlushTokenParser', false);
