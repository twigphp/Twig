<?php

/*
 * This file is part of Twig.
 *
 * (c) 2009 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Twig_TokenParser_Call extends Twig_TokenParser
{
  public function parse(Twig_Token $token)
  {
    $lineno = $token->getLine();
    $name = $this->parser->getStream()->expect(Twig_Token::NAME_TYPE)->getValue();

    // arguments
    $arguments = array();

    $this->parser->getStream()->expect(Twig_Token::BLOCK_END_TYPE);

    return new Twig_Node_Call($name, $arguments, $lineno, $this->getTag());
  }

  public function getTag()
  {
    return 'call';
  }
}
