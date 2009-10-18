<?php

class Twig_TokenParser_Set extends Twig_TokenParser
{
  public function parse(Twig_Token $token)
  {
    $lineno = $token->getLine();
    list($isMultitarget, $names) = $this->parser->getExpressionParser()->parseAssignmentExpression();
    $this->parser->getStream()->expect(Twig_Token::NAME_TYPE, 'as');
    $value = $this->parser->getExpressionParser()->parseExpression();

    $this->parser->getStream()->expect(Twig_Token::BLOCK_END_TYPE);

    return new Twig_Node_Set($isMultitarget, $names, $value, $lineno, $this->getTag());
  }

  public function getTag()
  {
    return 'set';
  }
}
