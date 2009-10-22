<?php

class Twig_TokenParser_Debug extends Twig_TokenParser
{
  public function parse(Twig_Token $token)
  {
    $lineno = $token->getLine();

    $expr = null;
    if (!$this->parser->getStream()->test(Twig_Token::BLOCK_END_TYPE))
    {
      $expr = $this->parser->getExpressionParser()->parseExpression();
    }
    $this->parser->getStream()->expect(Twig_Token::BLOCK_END_TYPE);

    return new Twig_Node_Debug($expr, $lineno, $this->getTag());
  }

  public function getTag()
  {
    return 'debug';
  }
}
