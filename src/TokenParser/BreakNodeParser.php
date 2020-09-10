<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 * (c) Armin Ronacher
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\TokenParser;

use Twig\Node\BreakNode;
use Twig\Token;

final class BreakNodeParser extends AbstractTokenParser
{
    public function parse(Token $token): BreakNode
    {
        $stream = $this->parser->getStream();

        if ($stream->test(Token::NUMBER_TYPE)) {
            $target = $stream->getCurrent()->getValue();
            $stream->next();
        } else {
            $target = 1;
        }

        $stream->expect(Token::BLOCK_END_TYPE);

        return new BreakNode($target, $token->getLine(), $this->getTag());
    }

    public function getTag(): string
    {
        return 'break';
    }
}
