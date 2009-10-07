<?php

/*
 * This file is part of Twig.
 *
 * (c) 2009 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Twig_TokenParser_Display extends Twig_TokenParser
{
  public function parse(Twig_Token $token)
  {
    $lineno = $token->getLine();
    $name = $this->parser->getStream()->expect(Twig_Token::NAME_TYPE)->getValue();
    if (!$this->parser->hasBlock($name))
    {
      throw new Twig_SyntaxError("The block '$name' cannot be displayed as it has not yet been defined", $lineno);
    }
    $this->parser->setCurrentBlock($name);
    $this->parser->getStream()->expect(Twig_Token::BLOCK_END_TYPE);

    return new Twig_Node_BlockReference($name, $lineno, $this->getTag());
  }

  public function getTag()
  {
    return 'display';
  }
}
