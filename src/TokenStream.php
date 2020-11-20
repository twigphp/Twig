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

namespace Twig;

use Twig\Error\SyntaxError;

/**
 * Represents a token stream.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
final class TokenStream
{
    /**
     * @var array
     */
    private $tokens;
    /**
     * @var int
     */
    private $current = 0;
    /**
     * @var Source|null
     */
    private $source;

    /**
     * TokenStream constructor.
     * @param array $tokens
     * @param Source|null $source
     */
    public function __construct(array $tokens, Source $source = null)
    {
        $this->tokens = $tokens;
        $this->source = $source ?: new Source('', '');
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return implode("\n", $this->tokens);
    }

    /**
     * @param array $tokens
     */
    public function injectTokens(array $tokens)
    {
        $this->tokens = array_merge(\array_slice($this->tokens, 0, $this->current), $tokens, \array_slice($this->tokens, $this->current));
    }

    /**
     * Sets the pointer to the next token and returns the old one.
     */
    public function next(): Token
    {
        if (!isset($this->tokens[++$this->current])) {
            throw new SyntaxError('Unexpected end of template.', $this->tokens[$this->current - 1]->getLine(), $this->source);
        }

        return $this->tokens[$this->current - 1];
    }

    /**
     * Tests a token, sets the pointer to the next one and returns it or throws a syntax error.
     *
     * @param $primary
     * @param null $secondary
     * @return Token|null The next token if the condition is true, null otherwise
     * @throws SyntaxError
     */
    public function nextIf($primary, $secondary = null)
    {
        if ($this->tokens[$this->current]->test($primary, $secondary)) {
            return $this->next();
        }
    }

    /**
     * Tests a token and returns it or throws a syntax error.
     * @param $type
     * @param null $value
     * @param string|null $message
     * @return Token
     * @throws SyntaxError
     */
    public function expect($type, $value = null, string $message = null): Token
    {
        $token = $this->tokens[$this->current];
        if (!$token->test($type, $value)) {
            $line = $token->getLine();
            throw new SyntaxError(sprintf('%sUnexpected token "%s"%s ("%s" expected%s).',
                $message ? $message.'. ' : '',
                Token::typeToEnglish($token->getType()),
                $token->getValue() ? sprintf(' of value "%s"', $token->getValue()) : '',
                Token::typeToEnglish($type), $value ? sprintf(' with value "%s"', $value) : ''),
                $line,
                $this->source
            );
        }
        $this->next();

        return $token;
    }

    /**
     * Looks at the next token.
     * @param int $number
     * @return Token
     * @throws SyntaxError
     */
    public function look(int $number = 1): Token
    {
        if (!isset($this->tokens[$this->current + $number])) {
            throw new SyntaxError('Unexpected end of template.', $this->tokens[$this->current + $number - 1]->getLine(), $this->source);
        }

        return $this->tokens[$this->current + $number];
    }

    /**
     * Tests the current token.
     * @param $primary
     * @param null $secondary
     * @return bool
     */
    public function test($primary, $secondary = null): bool
    {
        return $this->tokens[$this->current]->test($primary, $secondary);
    }

    /**
     * Checks if end of stream was reached.
     */
    public function isEOF(): bool
    {
        return /* Token::EOF_TYPE */ -1 === $this->tokens[$this->current]->getType();
    }

    /**
     * @return Token
     */
    public function getCurrent(): Token
    {
        return $this->tokens[$this->current];
    }

    /**
     * Gets the source associated with this stream.
     *
     * @internal
     */
    public function getSourceContext(): Source
    {
        return $this->source;
    }
}
