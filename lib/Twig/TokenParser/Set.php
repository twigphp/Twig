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
    list($isMultitarget, $names) = $this->parser->getExpressionParser()->parseAssignmentExpression();
    $this->parser->getStream()->expect(Twig_Token::NAME_TYPE, 'as');
    list(, $values) = $this->parser->getExpressionParser()->parseMultitargetExpression();

    $this->parser->getStream()->expect(Twig_Token::BLOCK_END_TYPE);

    if (count($names) !== count($values))
    {
      throw new Twig_SyntaxError("When using set, you must have the same number of variables and assignements.", $lineno);
    }

    return new Twig_Node_Set($isMultitarget, $names, $values, $lineno, $this->getTag());
  }

  public function getTag()
  {
    return 'set';
  }
}
