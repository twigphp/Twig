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

/**
 * Lexes a template string.
 *
 * @package    twig
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 */
class Twig_Lexer implements Twig_LexerInterface
{
    protected $tokens;
    protected $code;
    protected $cursor;
    protected $lineno;
    protected $end;
    protected $state;
    protected $brackets;

    protected $env;
    protected $filename;
    protected $options;
    protected $operatorRegex;

    const STATE_DATA  = 0;
    const STATE_BLOCK = 1;
    const STATE_VAR   = 2;

    const REGEX_NAME   = '/[A-Za-z_][A-Za-z0-9_]*/A';
    const REGEX_NUMBER = '/[0-9]+(?:\.[0-9]+)?/A';
    const REGEX_STRING = '/"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)"|\'([^\'\\\\]*(?:\\\\.[^\'\\\\]*)*)\'/As';
    const PUNCTUATION  = '()[]{}?:.,|';

    public function __construct(Twig_Environment $env, array $options = array())
    {
        $this->env = $env;

        $this->options = array_merge(array(
            'tag_comment'  => array('{#', '#}'),
            'tag_block'    => array('{%', '%}'),
            'tag_variable' => array('{{', '}}'),
        ), $options);
    }

    /**
     * Tokenizes a source code.
     *
     * @param  string $code     The source code
     * @param  string $filename A unique identifier for the source code
     *
     * @return Twig_TokenStream A token stream instance
     */
    public function tokenize($code, $filename = null)
    {
        if (function_exists('mb_internal_encoding') && ((int) ini_get('mbstring.func_overload')) & 2) {
            $mbEncoding = mb_internal_encoding();
            mb_internal_encoding('ASCII');
        }

        $this->code = str_replace(array("\r\n", "\r"), "\n", $code);
        $this->filename = $filename;
        $this->cursor = 0;
        $this->lineno = 1;
        $this->end = strlen($this->code);
        $this->tokens = array();
        $this->state = self::STATE_DATA;
        $this->brackets = array();

        while ($this->cursor < $this->end) {
            // dispatch to the lexing functions depending
            // on the current state
            switch ($this->state) {
                case self::STATE_DATA:
                    $this->lexData();
                    break;

                case self::STATE_BLOCK:
                    $this->lexBlock();
                    break;

                case self::STATE_VAR:
                    $this->lexVar();
                    break;
            }
        }

        $this->pushToken(Twig_Token::EOF_TYPE);

        if (!empty($this->brackets)) {
            list($expect, $lineno) = array_pop($this->brackets);
            throw new Twig_Error_Syntax(sprintf('Unclosed "%s"', $expect), $lineno, $this->filename);
        }

        if (isset($mbEncoding)) {
            mb_internal_encoding($mbEncoding);
        }

        return new Twig_TokenStream($this->tokens, $this->filename);
    }

    protected function lexData()
    {
        $pos = $this->end;
        if (false !== ($tmpPos = strpos($this->code, $this->options['tag_comment'][0], $this->cursor))  && $tmpPos < $pos) {
            $pos = $tmpPos;
            $token = $this->options['tag_comment'][0];
        }
        if (false !== ($tmpPos = strpos($this->code, $this->options['tag_variable'][0], $this->cursor)) && $tmpPos < $pos) {
            $pos = $tmpPos;
            $token = $this->options['tag_variable'][0];
        }
        if (false !== ($tmpPos = strpos($this->code, $this->options['tag_block'][0], $this->cursor))    && $tmpPos < $pos) {
            $pos = $tmpPos;
            $token = $this->options['tag_block'][0];
        }

        // if no matches are left we return the rest of the template as simple text token
        if ($pos === $this->end) {
            $this->pushToken(Twig_Token::TEXT_TYPE, substr($this->code, $this->cursor));
            $this->cursor = $this->end;
            return;
        }

        // push the template text first
        $text = substr($this->code, $this->cursor, $pos - $this->cursor);
        if (!empty($text)) {
            $this->pushToken(Twig_Token::TEXT_TYPE, $text);
        }
        $this->moveCursor($text.$token);

        switch ($token) {
            case $this->options['tag_comment'][0]:
                if (false === $pos = strpos($this->code, $this->options['tag_comment'][1], $this->cursor)) {
                    throw new Twig_Error_Syntax('unclosed comment', $this->lineno, $this->filename);
                }

                $this->moveCursor(substr($this->code, $this->cursor, $pos - $this->cursor) . $this->options['tag_comment'][1]);

                // mimicks the behavior of PHP by removing the newline that follows instructions if present
                if ("\n" === substr($this->code, $this->cursor, 1)) {
                    ++$this->cursor;
                    ++$this->lineno;
                }

                break;

            case $this->options['tag_block'][0]:
                // raw data?
                if (preg_match('/\s*raw\s*'.preg_quote($this->options['tag_block'][1], '/').'(.*?)'.preg_quote($this->options['tag_block'][0], '/').'\s*endraw\s*'.preg_quote($this->options['tag_block'][1], '/').'/As', $this->code, $match, null, $this->cursor)) {
                    $this->pushToken(Twig_Token::TEXT_TYPE, $match[1]);
                    $this->moveCursor($match[0]);
                    $this->state = self::STATE_DATA;
                } else {
                    $this->pushToken(Twig_Token::BLOCK_START_TYPE);
                    $this->state = self::STATE_BLOCK;
                }
                break;

            case $this->options['tag_variable'][0]:
                $this->pushToken(Twig_Token::VAR_START_TYPE);
                $this->state = self::STATE_VAR;
                break;
        }
    }

    protected function lexBlock()
    {
        if (empty($this->brackets) && preg_match('/\s*'.preg_quote($this->options['tag_block'][1], '/').'/A', $this->code, $match, null, $this->cursor)) {
            $this->pushToken(Twig_Token::BLOCK_END_TYPE);
            $this->moveCursor($match[0]);
            $this->state = self::STATE_DATA;

            // mimicks the behavior of PHP by removing the newline that follows instructions if present
            if ("\n" === substr($this->code, $this->cursor, 1)) {
                ++$this->cursor;
                ++$this->lineno;
            }
        }
        else {
            $this->lexExpression();
        }
    }

    protected function lexVar()
    {
        if (empty($this->brackets) && preg_match('/\s*'.preg_quote($this->options['tag_variable'][1], '/').'/A', $this->code, $match, null, $this->cursor)) {
            $this->pushToken(Twig_Token::VAR_END_TYPE);
            $this->moveCursor($match[0]);
            $this->state = self::STATE_DATA;
        }
        else {
            $this->lexExpression();
        }
    }

    protected function lexExpression()
    {
        // whitespace
        if (preg_match('/\s+/A', $this->code, $match, null, $this->cursor)) {
            $this->moveCursor($match[0]);

            if ($this->cursor >= $this->end) {
                throw new Twig_Error_Syntax('Unexpected end of file: Unclosed ' . ($this->state === self::STATE_BLOCK ? 'block' : 'variable'));
            }
        }

        // operators
        if (preg_match($this->getOperatorRegex(), $this->code, $match, null, $this->cursor)) {
            $this->pushToken(Twig_Token::OPERATOR_TYPE, $match[0]);
            $this->moveCursor($match[0]);
        }
        // names
        elseif (preg_match(self::REGEX_NAME, $this->code, $match, null, $this->cursor)) {
            $this->pushToken(Twig_Token::NAME_TYPE, $match[0]);
            $this->moveCursor($match[0]);
        }
        // numbers
        elseif (preg_match(self::REGEX_NUMBER, $this->code, $match, null, $this->cursor)) {
            $this->pushToken(Twig_Token::NUMBER_TYPE, ctype_digit($match[0]) ? (int) $match[0] : (float) $match[0]);
            $this->moveCursor($match[0]);
        }
        // punctuation
        elseif (false !== strpos(self::PUNCTUATION, $this->code[$this->cursor])) {
            // opening bracket
            if (false !== strpos('([{', $this->code[$this->cursor])) {
                $this->brackets[] = array($this->code[$this->cursor], $this->lineno);
            }
            // closing bracket
            elseif (false !== strpos(')]}', $this->code[$this->cursor])) {
                if (empty($this->brackets)) {
                    throw new Twig_Error_Syntax(sprintf('Unexpected "%s"', $this->code[$this->cursor]), $this->lineno, $this->filename);
                }

                list($expect, $lineno) = array_pop($this->brackets);
                if ($this->code[$this->cursor] != strtr($expect, '([{', ')]}')) {
                    throw new Twig_Error_Syntax(sprintf('Unclosed "%s"', $expect), $lineno, $this->filename);
                }
            }

            $this->pushToken(Twig_Token::PUNCTUATION_TYPE, $this->code[$this->cursor]);
            ++$this->cursor;
        }
        // strings
        elseif (preg_match(self::REGEX_STRING, $this->code, $match, null, $this->cursor)) {
            $this->pushToken(Twig_Token::STRING_TYPE, stripcslashes(substr($match[0], 1, -1)));
            $this->moveCursor($match[0]);
        }
        // unlexable
        else {
            throw new Twig_Error_Syntax(sprintf("Unexpected character '%s'", $this->code[$this->cursor]), $this->lineno, $this->filename);
        }
    }

    protected function pushToken($type, $value = '') {
        // do not push empty text tokens
        if (Twig_Token::TEXT_TYPE === $type && '' === $value) {
            return;
        }

        $this->tokens[] = new Twig_Token($type, $value, $this->lineno);
    }

    protected function moveCursor($text)
    {
        $this->cursor += strlen($text);
        $this->lineno += substr_count($text, "\n");
    }

    protected function getOperatorRegex()
    {
        if (null !== $this->operatorRegex) {
            return $this->operatorRegex;
        }

        $operators = array_merge(
            array('='),
            array_keys($this->env->getUnaryOperators()),
            array_keys($this->env->getBinaryOperators())
        );

        $operators = array_combine($operators, array_map('strlen', $operators));
        arsort($operators);

        $regex = array();
        foreach ($operators as $operator => $length) {
            // an operator that ends with a character must be followed by
            // a whitespace or a parenthesis
            if (ctype_alpha($operator[$length - 1])) {
                $regex[] = preg_quote($operator, '/').'(?=[ ()])';
            } else {
                $regex[] = preg_quote($operator, '/');
            }
        }

        return $this->operatorRegex = '/'.implode('|', $regex).'/A';
    }
}
