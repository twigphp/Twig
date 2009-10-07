<?php

/*
 * This file is part of Twig.
 *
 * (c) 2009 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Twig_TokenParser_AutoEscape extends Twig_TokenParser
{
  public function parse(Twig_Token $token)
  {
    $lineno = $token->getLine();
    $value = $this->parser->getStream()->expect(Twig_Token::NAME_TYPE)->getValue();
    if (!in_array($value, array('on', 'off')))
    {
      throw new Twig_SyntaxError("Autoescape value must be 'on' or 'off'", $lineno);
    }

    $this->parser->getStream()->expect(Twig_Token::BLOCK_END_TYPE);
    $body = $this->parser->subparse(array($this, 'decideBlockEnd'), true);
    $this->parser->getStream()->expect(Twig_Token::BLOCK_END_TYPE);

    return new Twig_Node_AutoEscape('on' === $value ? true : false, $body, $lineno, $this->getTag());
  }

  public function decideBlockEnd($token)
  {
    return $token->test('endautoescape');
  }

  public function getTag()
  {
    return 'autoescape';
  }
}
