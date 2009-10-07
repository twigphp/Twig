<?php

class Twig_TokenParser_Set extends Twig_TokenParser
{
  public function parse(Twig_Token $token)
  {
    $lineno = $token->getLine();
    $name = $this->parser->getStream()->expect(Twig_Token::NAME_TYPE)->getValue();
    $value = $this->parser->getExpressionParser()->parseExpression();

    $this->parser->getStream()->expect(Twig_Token::BLOCK_END_TYPE);

    return new Twig_Node_Set($name, $value, $lineno, $this->getTag());
  }

  public function getTag()
  {
    return 'set';
  }
}
