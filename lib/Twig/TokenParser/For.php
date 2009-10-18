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
class Twig_TokenParser_For extends Twig_TokenParser
{
  public function parse(Twig_Token $token)
  {
    $lineno = $token->getLine();
    list($isMultitarget, $item) = $this->parser->getExpressionParser()->parseAssignmentExpression();
    $this->parser->getStream()->expect('in');
    $seq = $this->parser->getExpressionParser()->parseExpression();
    $this->parser->getStream()->expect(Twig_Token::BLOCK_END_TYPE);
    $body = $this->parser->subparse(array($this, 'decideForFork'));
    if ($this->parser->getStream()->next()->getValue() == 'else')
    {
      $this->parser->getStream()->expect(Twig_Token::BLOCK_END_TYPE);
      $else = $this->parser->subparse(array($this, 'decideForEnd'), true);
    }
    else
    {
      $else = null;
    }
    $this->parser->getStream()->expect(Twig_Token::BLOCK_END_TYPE);

    return new Twig_Node_For($isMultitarget, $item, $seq, $body, $else, $lineno, $this->getTag());
  }

  public function decideForFork($token)
  {
    return $token->test(array('else', 'endfor'));
  }

  public function decideForEnd($token)
  {
    return $token->test('endfor');
  }

  public function getTag()
  {
    return 'for';
  }
}
