<?php

/*
 * This file is part of Twig.
 *
 * (c) 2009 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Twig_TokenParser_Import extends Twig_TokenParser
{
  public function parse(Twig_Token $token)
  {
    $macro = $this->parser->getStream()->expect(Twig_Token::STRING_TYPE)->getValue();
    $this->parser->getStream()->expect('as');
    $var = $this->parser->getStream()->expect(Twig_Token::NAME_TYPE)->getValue();
    $this->parser->getStream()->expect(Twig_Token::BLOCK_END_TYPE);

    return new Twig_Node_Import($macro, $var, $token->getLine(), $this->getTag());
  }

  public function getTag()
  {
    return 'import';
  }
}
