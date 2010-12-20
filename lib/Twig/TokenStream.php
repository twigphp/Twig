<?php

/*
 * This file is part of Twig.
 *
 * (c) 2009 Fabien Potencier
 * (c) 2009 Armin Ronacher
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Twig_TokenStream
{
    protected $pushed;
    protected $originalTokens;
    protected $tokens;
    protected $eof;
    protected $current;
    protected $filename;

    public function __construct(array $tokens, $filename)
    {
        $this->pushed = array();
        $this->originalTokens = $tokens;
        $this->tokens = $tokens;
        $this->filename = $filename;
        $this->next();
    }

    public function __toString()
    {
        $repr = '';
        foreach ($this->originalTokens as $token) {
            $repr .= $token."\n";
        }

        return $repr;
    }

    public function push($token)
    {
        $this->pushed[] = $token;
    }

    /**
     * Sets the pointer to the next token and returns the old one.
     *
     * @param Boolean $fromStack Whether to get a token from the stack or not
     */
    public function next($fromStack = true)
    {
        if ($fromStack && !empty($this->pushed)) {
            $old = array_shift($this->pushed);
            $token = array_shift($this->pushed);
        } else {
            $old = $this->current;
            $token = array_shift($this->tokens);
        }

        if (null === $token) {
            throw new Twig_Error_Syntax('Unexpected end of template', -1);
        }

        $this->current = $token;

        $this->eof = $token->getType() === Twig_Token::EOF_TYPE;

        return $old;
    }

    /**
     * Looks at the next token.
     */
    public function look()
    {
        $old = $this->next(false);
        $new = $this->current;
        $this->push($old);
        $this->push($new);

        return $new;
    }

    /**
     * Rewinds the pushed tokens.
     */
    public function rewind()
    {
        $tokens = array();
        while ($this->pushed) {
            $tokens[] = array_shift($this->pushed);
            array_shift($this->pushed);
        }

        $this->tokens = array_merge($tokens, array($this->current), $this->tokens);

        $this->next();
    }

    /**
     * Expects a token (like $token->test()) and returns it or throw a syntax error.
     */
    public function expect($primary, $secondary = null, $message = null)
    {
        $token = $this->current;
        if (!$token->test($primary, $secondary)) {
            throw new Twig_Error_Syntax(sprintf('%sUnexpected token "%s" of value "%s" ("%s" expected%s)',
                $message ? $message.'. ' : '',
                Twig_Token::typeToEnglish($token->getType()), $token->getValue(),
                Twig_Token::typeToEnglish($primary), $secondary ? sprintf(' with value "%s"', $secondary) : ''),
                $this->current->getLine()
            );
        }
        $this->next();

        return $token;
    }

    /**
     * Forwards that call to the current token.
     */
    public function test($primary, $secondary = null)
    {
        return $this->current->test($primary, $secondary);
    }

    public function isEOF()
    {
        return $this->eof;
    }

    public function getCurrent()
    {
        return $this->current;
    }

    public function getFilename()
    {
        return $this->filename;
    }
}
