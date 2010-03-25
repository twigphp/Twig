<?php

/*
 * This file is part of Twig.
 *
 * (c) 2009 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Twig_TokenParser_Set extends Twig_TokenParser
{
  public function parse(Twig_Token $token)
  {
    $lineno = $token->getLine();
    $stream = $this->parser->getStream();
    list($isMultitarget, $names) = $this->parser->getExpressionParser()->parseAssignmentExpression();

    $capture = false;
    if ($stream->test(Twig_Token::NAME_TYPE, 'as'))
    {
      $stream->expect(Twig_Token::NAME_TYPE, 'as');
      list(, $values) = $this->parser->getExpressionParser()->parseMultitargetExpression();

      $stream->expect(Twig_Token::BLOCK_END_TYPE);
    }
    else
    {
      $capture = true;

      if ($isMultitarget)
      {
        throw new Twig_SyntaxError("When using set with a block, you cannot have a multi-target.", $lineno);
      }

      $stream->expect(Twig_Token::BLOCK_END_TYPE);

      $values = $this->parser->subparse(array($this, 'decideBlockEnd'), true);
      $stream->expect(Twig_Token::BLOCK_END_TYPE);
    }

    if (count($names) !== count($values))
    {
      throw new Twig_SyntaxError("When using set, you must have the same number of variables and assignements.", $lineno);
    }

    return new Twig_Node_Set($isMultitarget, $capture, $names, $values, $lineno, $this->getTag());
  }

  public function decideBlockEnd($token)
  {
    return $token->test('endset');
  }

  public function getTag()
  {
    return 'set';
  }
}
