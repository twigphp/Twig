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
class Twig_TokenParser_If extends Twig_TokenParser
{
  public function parse(Twig_Token $token)
  {
    $lineno = $token->getLine();
    $expr = $this->parser->getExpressionParser()->parseExpression();
    $this->parser->getStream()->expect(Twig_Token::BLOCK_END_TYPE);
    $body = $this->parser->subparse(array($this, 'decideIfFork'));
    $tests = array(array($expr, $body));
    $else = null;

    $end = false;
    while (!$end)
    {
      switch ($this->parser->getStream()->next()->getValue())
      {
        case 'else':
          $this->parser->getStream()->expect(Twig_Token::BLOCK_END_TYPE);
          $else = $this->parser->subparse(array($this, 'decideIfEnd'));
          break;

        case 'elseif':
          $expr = $this->parser->getExpressionParser()->parseExpression();
          $this->parser->getStream()->expect(Twig_Token::BLOCK_END_TYPE);
          $body = $this->parser->subparse(array($this, 'decideIfFork'));
          $tests[] = array($expr, $body);
          break;

        case 'endif':
          $end = true;
          break;
      }
    }

    $this->parser->getStream()->expect(Twig_Token::BLOCK_END_TYPE);

    return new Twig_Node_If($tests, $else, $lineno, $this->getTag());
  }

  public function decideIfFork($token)
  {
    return $token->test(array('elseif', 'else', 'endif'));
  }

  public function decideIfEnd($token)
  {
    return $token->test(array('endif'));
  }

  public function getTag()
  {
    return 'if';
  }
}
