<?php

/*
 * This file is part of Twig.
 *
 * (c) 2009 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Twig_TokenParser_Filter extends Twig_TokenParser
{
  public function parse(Twig_Token $token)
  {
    $lineno = $token->getLine();
    $filters = $this->parser->getExpressionParser()->parseFilterExpressionRaw();

    $this->parser->getStream()->expect(Twig_Token::BLOCK_END_TYPE);
    $body = $this->parser->subparse(array($this, 'decideBlockEnd'), true);
    $this->parser->getStream()->expect(Twig_Token::BLOCK_END_TYPE);

    return new Twig_Node_Filter($filters, $body, $lineno, $this->getTag());
  }

  public function decideBlockEnd($token)
  {
    return $token->test('endfilter');
  }

  public function getTag()
  {
    return 'filter';
  }
}
