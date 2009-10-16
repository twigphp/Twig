<?php

/*
 * This file is part of Twig.
 *
 * (c) 2009 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Twig_TokenParser_Macro extends Twig_TokenParser
{
  public function parse(Twig_Token $token)
  {
    $lineno = $token->getLine();
    $name = $this->parser->getStream()->expect(Twig_Token::NAME_TYPE)->getValue();

    $arguments = $this->parser->getExpressionParser()->parseArguments();

    $this->parser->getStream()->expect(Twig_Token::BLOCK_END_TYPE);
    $body = $this->parser->subparse(array($this, 'decideBlockEnd'), true);
    $this->parser->getStream()->expect(Twig_Token::BLOCK_END_TYPE);

    $this->parser->setMacro($name, new Twig_Node_Macro($name, $body, $arguments, $lineno, $this->getTag()));

    return null;
  }

  public function decideBlockEnd($token)
  {
    return $token->test('endmacro');
  }

  public function getTag()
  {
    return 'macro';
  }
}
