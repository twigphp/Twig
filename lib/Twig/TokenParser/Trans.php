<?php

/*
 * This file is part of Twig.
 *
 * (c) 2010 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Twig_TokenParser_Trans extends Twig_TokenParser
{
  public function parse(Twig_Token $token)
  {
    $lineno = $token->getLine();
    $stream = $this->parser->getStream();
    $count = false;
    if (!$stream->test(Twig_Token::BLOCK_END_TYPE))
    {
      $count = new Twig_Node_Expression_Name($stream->expect(Twig_Token::NAME_TYPE)->getValue(), $lineno);
    }

    $stream->expect(Twig_Token::BLOCK_END_TYPE);
    $body = $this->parser->subparse(array($this, 'decideForFork'));
    $plural = false;
    if ('plural' === $stream->next()->getValue())
    {
      $stream->expect(Twig_Token::BLOCK_END_TYPE);
      $plural = $this->parser->subparse(array($this, 'decideForEnd'), true);

      if (false === $count)
      {
        throw new Twig_SyntaxError('When a plural is used, you must pass the count as an argument to the "trans" tag', $lineno);
      }
    }
    $stream->expect(Twig_Token::BLOCK_END_TYPE);

    return new Twig_Node_Trans($count, $body, $plural, $lineno, $this->getTag());
  }

  public function decideForFork($token)
  {
    return $token->test(array('plural', 'endtrans'));
  }

  public function decideForEnd($token)
  {
    return $token->test('endtrans');
  }

  public function getTag()
  {
    return 'trans';
  }
}
