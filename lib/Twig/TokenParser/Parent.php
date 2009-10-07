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
class Twig_TokenParser_Parent extends Twig_TokenParser
{
  public function parse(Twig_Token $token)
  {
    if (null === $this->parser->getCurrentBlock())
    {
      throw new Twig_SyntaxError('Calling "parent" outside a block is forbidden', $token->getLine());
    }
    $this->parser->getStream()->expect(Twig_Token::BLOCK_END_TYPE);

    return new Twig_Node_Parent($this->parser->getCurrentBlock(), $token->getLine(), $this->getTag());
  }

  public function getTag()
  {
    return 'parent';
  }
}
