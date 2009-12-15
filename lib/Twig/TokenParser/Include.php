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
class Twig_TokenParser_Include extends Twig_TokenParser
{
  public function parse(Twig_Token $token)
  {
    $expr = $this->parser->getExpressionParser()->parseExpression();

    $sandboxed = false;
    if ($this->parser->getStream()->test(Twig_Token::NAME_TYPE, 'sandboxed'))
    {
      $this->parser->getStream()->next();
      $sandboxed = true;
    }

    $variables = null;
    if ($this->parser->getStream()->test(Twig_Token::NAME_TYPE, 'with'))
    {
      $this->parser->getStream()->next();
      $variables = $this->parser->getExpressionParser()->parseExpression();
    }

    $this->parser->getStream()->expect(Twig_Token::BLOCK_END_TYPE);

    return new Twig_Node_Include($expr, $sandboxed, $variables, $token->getLine(), $this->getTag());
  }

  public function getTag()
  {
    return 'include';
  }
}
