<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Extra\Cache\TokenParser;

use Twig\Error\SyntaxError;
use Twig\Extra\Cache\Node\CacheNode;
use Twig\Node\Expression\Filter\RawFilter;
use Twig\Node\Node;
use Twig\Node\PrintNode;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

class CacheTokenParser extends AbstractTokenParser
{
    public function parse(Token $token): Node
    {
        $stream = $this->parser->getStream();
        $key = $this->parser->parseExpression();

        $ttl = null;
        $tags = null;
        while ($stream->test(Token::NAME_TYPE)) {
            $k = $stream->getCurrent()->getValue();
            if (!\in_array($k, ['ttl', 'tags'], true)) {
                throw new SyntaxError(\sprintf('Unknown "%s" configuration.', $k), $stream->getCurrent()->getLine(), $stream->getSourceContext());
            }

            $stream->next();
            $stream->expect(Token::OPERATOR_TYPE, '(');
            $line = $stream->getCurrent()->getLine();
            if ($stream->test(Token::PUNCTUATION_TYPE, ')')) {
                throw new SyntaxError(\sprintf('The "%s" modifier takes exactly one argument (0 given).', $k), $line, $stream->getSourceContext());
            }
            $arg = $this->parser->parseExpression();
            if ($stream->test(Token::PUNCTUATION_TYPE, ',')) {
                throw new SyntaxError(\sprintf('The "%s" modifier takes exactly one argument (2 given).', $k), $line, $stream->getSourceContext());
            }
            $stream->expect(Token::PUNCTUATION_TYPE, ')');

            if ('ttl' === $k) {
                $ttl = $arg;
            } elseif ('tags' === $k) {
                $tags = $arg;
            }
        }

        $stream->expect(Token::BLOCK_END_TYPE);
        $body = $this->parser->subparse([$this, 'decideCacheEnd'], true);
        $stream->expect(Token::BLOCK_END_TYPE);

        $body = new CacheNode($key, $ttl, $tags, $body, $token->getLine());

        return new PrintNode(new RawFilter($body), $token->getLine());
    }

    public function decideCacheEnd(Token $token): bool
    {
        return $token->test('endcache');
    }

    public function getTag(): string
    {
        return 'cache';
    }
}
