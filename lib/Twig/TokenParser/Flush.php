<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Twig\Node\FlushNode;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
 * Flushes the output to the client.
 *
 * @see flush()
 *
 * @final
 */
class Twig_TokenParser_Flush extends AbstractTokenParser
{
    public function parse(Token $token)
    {
        $this->parser->getStream()->expect(Token::BLOCK_END_TYPE);

        return new FlushNode($token->getLine(), $this->getTag());
    }

    public function getTag()
    {
        return 'flush';
    }
}

class_alias('Twig_TokenParser_Flush', 'Twig\TokenParser\FlushTokenParser', false);
