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
class Twig_TokenParser_Extends extends Twig_TokenParser
{
  public function parse(Twig_Token $token)
  {
    if (null !== $this->parser->getParent())
    {
      throw new Twig_SyntaxError('Multiple extend tags are forbidden', $token->getLine());
    }
    $this->parser->setParent($this->parser->getStream()->expect(Twig_Token::STRING_TYPE)->getValue());
    $this->parser->getStream()->expect(Twig_Token::BLOCK_END_TYPE);

    return null;
  }

  public function getTag()
  {
    return 'extends';
  }
}
