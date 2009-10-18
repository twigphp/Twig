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
 * @version    SVN: $Id$
 */
class Twig_Lexer implements Twig_LexerInterface
{
  protected $cursor;
  protected $position;
  protected $end;
  protected $pushedBack;
  protected $code;
  protected $lineno;
  protected $filename;
  protected $env;
  protected $options;

  const POSITION_DATA  = 0;
  const POSITION_BLOCK = 1;
  const POSITION_VAR   = 2;

  const REGEX_NAME     = '/[A-Za-z_][A-Za-z0-9_]*/A';
  const REGEX_NUMBER   = '/[0-9]+(?:\.[0-9]+)?/A';
  const REGEX_STRING   = '/(?:"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)"|\'([^\'\\\\]*(?:\\\\.[^\'\\\\]*)*)\')/Asm';
  const REGEX_OPERATOR = '/<=? | >=? | [!=]= | [(){}.,%*\/+~|-] | \[ | \] | \? | \:/Ax';

  public function __construct(Twig_Environment $env = null, array $options = array())
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
  public function tokenize($code, $filename = 'n/a')
  {
    $this->code = preg_replace('/(\r\n|\r|\n)/', '\n', $code);
    $this->filename = $filename;
    $this->cursor = 0;
    $this->lineno = 1;
    $this->pushedBack = array();
    $this->end = strlen($this->code);
    $this->position = self::POSITION_DATA;

    $tokens = array();
    $end = false;
    while (!$end)
    {
      $token = $this->nextToken();

      $tokens[] = $token;

      $end = $token->getType() === Twig_Token::EOF_TYPE;
    }

    return new Twig_TokenStream($tokens, $this->filename, $this->env->getTrimBlocks());
  }

  public function setEnvironment(Twig_Environment $env)
  {
    $this->env = $env;
  }

  /**
   * Parses the next token and returns it.
   */
  protected function nextToken()
  {
    // do we have tokens pushed back? get one
    if (!empty($this->pushedBack))
    {
      return array_shift($this->pushedBack);
    }

    // have we reached the end of the code?
    if ($this->cursor >= $this->end)
    {
      return new Twig_Token(Twig_Token::EOF_TYPE, '', $this->lineno);
    }

    // otherwise dispatch to the lexing functions depending
    // on our current position in the code.
    switch ($this->position)
    {
      case self::POSITION_DATA:
        $tokens = $this->lexData();
        break;

      case self::POSITION_BLOCK:
        $tokens = $this->lexBlock();
        break;

      case self::POSITION_VAR:
        $tokens = $this->lexVar();
        break;
    }

    // if the return value is not an array it's a token
    if (!is_array($tokens))
    {
      return $tokens;
    }
    // empty array, call again
    else if (empty($tokens))
    {
      return $this->nextToken();
    }
    // if we have multiple items we push them to the buffer
    else if (count($tokens) > 1)
    {
      $first = array_shift($tokens);
      $this->pushedBack = $tokens;

      return $first;
    }
    // otherwise return the first item of the array.
    else
    {
      return $tokens[0];
    }
  }

  protected function lexData()
  {
    $match = null;

    // if no matches are left we return the rest of the template
    // as simple text token
    if (!preg_match('/(.*?)('.preg_quote($this->options['tag_comment'][0], '/').'|'.preg_quote($this->options['tag_variable'][0], '/').'|'.preg_quote($this->options['tag_block'][0], '/').')/A', $this->code, $match, null, $this->cursor))
    {
      $rv = new Twig_Token(Twig_Token::TEXT_TYPE, substr($this->code, $this->cursor), $this->lineno);
      $this->cursor = $this->end;

      return $rv;
    }

    // update the lineno on the instance
    $lineno = $this->lineno;

    $this->cursor += strlen($match[0]);
    $this->lineno += substr_count($match[0], '\n');

    // array of tokens
    $result = array();

    // push the template text first
    $text = $match[1];
    if (!empty($text))
    {
      $result[] = new Twig_Token(Twig_Token::TEXT_TYPE, $text, $lineno);
      $lineno += substr_count($text, '\n');
    }

    $token = $match[2];
    switch ($token)
    {
      case $this->options['tag_comment'][0]:
        if (!preg_match('/(.*?)'.preg_quote($this->options['tag_comment'][1], '/').'/A', $this->code, $match, null, $this->cursor))
        {
          throw new Twig_SyntaxError('unclosed comment', $this->lineno, $this->filename);
        }
        $this->cursor += strlen($match[0]);
        $this->lineno += substr_count($match[0], '\n');
        break;

      case $this->options['tag_block'][0]:
        // raw data?
        if (preg_match('/\s*raw\s*'.preg_quote($this->options['tag_block'][1], '/').'(.*?)'.preg_quote($this->options['tag_block'][0], '/').'\s*endraw\s*'.preg_quote($this->options['tag_block'][1], '/').'/A', $this->code, $match, null, $this->cursor))
        {
          $result[] = new Twig_Token(Twig_Token::TEXT_TYPE, $match[1], $lineno);
          $this->cursor += strlen($match[0]);
          $this->lineno += substr_count($match[0], '\n');
          $this->position = self::POSITION_DATA;
        }
        else
        {
          $result[] = new Twig_Token(Twig_Token::BLOCK_START_TYPE, '', $lineno);
          $this->position = self::POSITION_BLOCK;
        }
        break;

      case $this->options['tag_variable'][0]:
        $result[] = new Twig_Token(Twig_Token::VAR_START_TYPE, '', $lineno);
        $this->position = self::POSITION_VAR;
        break;
    }

    return $result;
  }

  protected function lexBlock()
  {
    if (preg_match('/\s*'.preg_quote($this->options['tag_block'][1], '/').'/A', $this->code, $match, null, $this->cursor))
    {
      $lineno = $this->lineno;
      $this->cursor += strlen($match[0]);
      $this->lineno += substr_count($match[0], '\n');
      $this->position = self::POSITION_DATA;

      return new Twig_Token(Twig_Token::BLOCK_END_TYPE, '', $lineno);
    }

    return $this->lexExpression();
  }

  protected function lexVar()
  {
    if (preg_match('/\s*'.preg_quote($this->options['tag_variable'][1], '/').'/A', $this->code, $match, null, $this->cursor))
    {
      $lineno = $this->lineno;
      $this->cursor += strlen($match[0]);
      $this->lineno += substr_count($match[0], '\n');
      $this->position = self::POSITION_DATA;

      return new Twig_Token(Twig_Token::VAR_END_TYPE, '', $lineno);
    }

    return $this->lexExpression();
  }

  protected function lexExpression()
  {
    $match = null;

    // whitespace
    while (preg_match('/\s+/A', $this->code, $match, null, $this->cursor))
    {
      $this->cursor += strlen($match[0]);
      $this->lineno += substr_count($match[0], '\n');
    }

    // sanity check
    if ($this->cursor >= $this->end)
    {
      throw new Twig_SyntaxError('Unexpected end of stream', $this->lineno, $this->filename);
    }

    // first parse operators
    if (preg_match(self::REGEX_OPERATOR, $this->code, $match, null, $this->cursor))
    {
      $this->cursor += strlen($match[0]);

      return new Twig_Token(Twig_Token::OPERATOR_TYPE, $match[0], $this->lineno);
    }
    // now names
    else if (preg_match(self::REGEX_NAME, $this->code, $match, null, $this->cursor))
    {
      $this->cursor += strlen($match[0]);

      return new Twig_Token(Twig_Token::NAME_TYPE, $match[0], $this->lineno);
    }
    // then numbers
    else if (preg_match(self::REGEX_NUMBER, $this->code, $match, null, $this->cursor))
    {
      $this->cursor += strlen($match[0]);
      $value = (float)$match[0];
      if ((int)$value === $value)
      {
        $value = (int)$value;
      }

      return new Twig_Token(Twig_Token::NUMBER_TYPE, $value, $this->lineno);
    }
    // and finally strings
    else if (preg_match(self::REGEX_STRING, $this->code, $match, null, $this->cursor))
    {
      $this->cursor += strlen($match[0]);
      $this->lineno += substr_count($match[0], '\n');
      $value = stripcslashes(substr($match[0], 1, strlen($match[0]) - 2));

      return new Twig_Token(Twig_Token::STRING_TYPE, $value, $this->lineno);
    }

    // unlexable
    throw new Twig_SyntaxError(sprintf("Unexpected character '%s'", $this->code[$this->cursor]), $this->lineno, $this->filename);
  }
}
