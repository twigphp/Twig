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

/**
 * Parses expressions.
 *
 * @package    twig
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
class Twig_ExpressionParser
{
  protected $parser;

  public function __construct(Twig_Parser $parser)
  {
    $this->parser = $parser;
  }

  public function parseExpression()
  {
    return $this->parseConditionalExpression();
  }

  public function parseConditionalExpression()
  {
    $lineno = $this->parser->getCurrentToken()->getLine();
    $expr1 = $this->parseOrExpression();
    while ($this->parser->getStream()->test(Twig_Token::OPERATOR_TYPE, '?'))
    {
      $this->parser->getStream()->next();
      $expr2 = $this->parseOrExpression();
      $this->parser->getStream()->expect(Twig_Token::OPERATOR_TYPE, ':');
      $expr3 = $this->parseConditionalExpression();
      $expr1 = new Twig_Node_Expression_Conditional($expr1, $expr2, $expr3, $lineno);
      $lineno = $this->parser->getCurrentToken()->getLine();
    }

    return $expr1;
  }

  public function parseOrExpression()
  {
    $lineno = $this->parser->getCurrentToken()->getLine();
    $left = $this->parseAndExpression();
    while ($this->parser->getStream()->test('or'))
    {
      $this->parser->getStream()->next();
      $right = $this->parseAndExpression();
      $left = new Twig_Node_Expression_Binary_Or($left, $right, $lineno);
      $lineno = $this->parser->getCurrentToken()->getLine();
    }

    return $left;
  }

  public function parseAndExpression()
  {
    $lineno = $this->parser->getCurrentToken()->getLine();
    $left = $this->parseCompareExpression();
    while ($this->parser->getStream()->test('and'))
    {
      $this->parser->getStream()->next();
      $right = $this->parseCompareExpression();
      $left = new Twig_Node_Expression_Binary_And($left, $right, $lineno);
      $lineno = $this->parser->getCurrentToken()->getLine();
    }

    return $left;
  }

  public function parseCompareExpression()
  {
    static $operators = array('==', '!=', '<', '>', '>=', '<=');
    $lineno = $this->parser->getCurrentToken()->getLine();
    $expr = $this->parseAddExpression();
    $ops = array();
    while (
      $this->parser->getStream()->test(Twig_Token::OPERATOR_TYPE, $operators)
      ||
      $this->parser->getStream()->test(Twig_Token::NAME_TYPE, 'in')
    )
    {
      $ops[] = array($this->parser->getStream()->next()->getValue(), $this->parseAddExpression());
    }

    if (empty($ops))
    {
      return $expr;
    }

    return new Twig_Node_Expression_Compare($expr, $ops, $lineno);
  }

  public function parseAddExpression()
  {
    $lineno = $this->parser->getCurrentToken()->getLine();
    $left = $this->parseSubExpression();
    while ($this->parser->getStream()->test(Twig_Token::OPERATOR_TYPE, '+'))
    {
      $this->parser->getStream()->next();
      $right = $this->parseSubExpression();
      $left = new Twig_Node_Expression_Binary_Add($left, $right, $lineno);
      $lineno = $this->parser->getCurrentToken()->getLine();
    }

    return $left;
  }

  public function parseSubExpression()
  {
    $lineno = $this->parser->getCurrentToken()->getLine();
    $left = $this->parseConcatExpression();
    while ($this->parser->getStream()->test(Twig_Token::OPERATOR_TYPE, '-'))
    {
      $this->parser->getStream()->next();
      $right = $this->parseConcatExpression();
      $left = new Twig_Node_Expression_Binary_Sub($left, $right, $lineno);
      $lineno = $this->parser->getCurrentToken()->getLine();
    }

    return $left;
  }

  public function parseConcatExpression()
  {
    $lineno = $this->parser->getCurrentToken()->getLine();
    $left = $this->parseMulExpression();
    while ($this->parser->getStream()->test(Twig_Token::OPERATOR_TYPE, '~'))
    {
      $this->parser->getStream()->next();
      $right = $this->parseMulExpression();
      $left = new Twig_Node_Expression_Binary_Concat($left, $right, $lineno);
      $lineno = $this->parser->getCurrentToken()->getLine();
    }

    return $left;
  }

  public function parseMulExpression()
  {
    $lineno = $this->parser->getCurrentToken()->getLine();
    $left = $this->parseDivExpression();
    while ($this->parser->getStream()->test(Twig_Token::OPERATOR_TYPE, '*'))
    {
      $this->parser->getStream()->next();
      $right = $this->parseDivExpression();
      $left = new Twig_Node_Expression_Binary_Mul($left, $right, $lineno);
      $lineno = $this->parser->getCurrentToken()->getLine();
    }

    return $left;
  }

  public function parseDivExpression()
  {
    $lineno = $this->parser->getCurrentToken()->getLine();
    $left = $this->parseFloorDivExpression();
    while ($this->parser->getStream()->test(Twig_Token::OPERATOR_TYPE, '/'))
    {
      $this->parser->getStream()->next();
      $right = $this->parseModExpression();
      $left = new Twig_Node_Expression_Binary_Div($left, $right, $lineno);
      $lineno = $this->parser->getCurrentToken()->getLine();
    }

    return $left;
  }

  public function parseFloorDivExpression()
  {
    $lineno = $this->parser->getCurrentToken()->getLine();
    $left = $this->parseModExpression();
    while ($this->parser->getStream()->test(Twig_Token::OPERATOR_TYPE, '//'))
    {
      $this->parser->getStream()->next();
      $right = $this->parseModExpression();
      $left = new Twig_Node_Expression_Binary_FloorDiv($left, $right, $lineno);
      $lineno = $this->parser->getCurrentToken()->getLine();
    }

    return $left;
  }

  public function parseModExpression()
  {
    $lineno = $this->parser->getCurrentToken()->getLine();
    $left = $this->parseUnaryExpression();
    while ($this->parser->getStream()->test(Twig_Token::OPERATOR_TYPE, '%'))
    {
      $this->parser->getStream()->next();
      $right = $this->parseUnaryExpression();
      $left = new Twig_Node_Expression_Binary_Mod($left, $right, $lineno);
      $lineno = $this->parser->getCurrentToken()->getLine();
    }

    return $left;
  }

  public function parseUnaryExpression()
  {
    if ($this->parser->getStream()->test('not'))
    {
      return $this->parseNotExpression();
    }
    if ($this->parser->getCurrentToken()->getType() == Twig_Token::OPERATOR_TYPE)
    {
      switch ($this->parser->getCurrentToken()->getValue())
      {
        case '-':
          return $this->parseNegExpression();
        case '+':
          return $this->parsePosExpression();
      }
    }

    return $this->parsePrimaryExpression();
  }

  public function parseNotExpression()
  {
    $token = $this->parser->getStream()->next();
    $node = $this->parseUnaryExpression();

    return new Twig_Node_Expression_Unary_Not($node, $token->getLine());
  }

  public function parseNegExpression()
  {
    $token = $this->parser->getStream()->next();
    $node = $this->parseUnaryExpression();

    return new Twig_Node_Expression_Unary_Neg($node, $token->getLine());
  }

  public function parsePosExpression()
  {
    $token = $this->parser->getStream()->next();
    $node = $this->parseUnaryExpression();

    return new Twig_Node_Expression_Unary_Pos($node, $token->getLine());
  }

  public function parsePrimaryExpression($assignment = false)
  {
    $token = $this->parser->getCurrentToken();
    switch ($token->getType())
    {
      case Twig_Token::NAME_TYPE:
        $this->parser->getStream()->next();
        switch ($token->getValue())
        {
          case 'true':
            $node = new Twig_Node_Expression_Constant(true, $token->getLine());
            break;

          case 'false':
            $node = new Twig_Node_Expression_Constant(false, $token->getLine());
            break;

          case 'none':
            $node = new Twig_Node_Expression_Constant(null, $token->getLine());
            break;

          default:
            $cls = $assignment ? 'Twig_Node_Expression_AssignName' : 'Twig_Node_Expression_Name';
            $node = new $cls($token->getValue(), $token->getLine());
        }
        break;

      case Twig_Token::NUMBER_TYPE:
      case Twig_Token::STRING_TYPE:
        $this->parser->getStream()->next();
        $node = new Twig_Node_Expression_Constant($token->getValue(), $token->getLine());
        break;

      default:
        if ($token->test(Twig_Token::OPERATOR_TYPE, '['))
        {
          $this->parser->getStream()->next();
          $node = $this->parseArrayExpression();
          $this->parser->getStream()->expect(Twig_Token::OPERATOR_TYPE, ']');
        }
        elseif ($token->test(Twig_Token::OPERATOR_TYPE, '('))
        {
          $this->parser->getStream()->next();
          $node = $this->parseExpression();
          $this->parser->getStream()->expect(Twig_Token::OPERATOR_TYPE, ')');
        }
        else
        {
          throw new Twig_SyntaxError(sprintf('Unexpected token "%s"', $token->getValue()), $token->getLine());
        }
    }
    if (!$assignment)
    {
      $node = $this->parsePostfixExpression($node);
    }

    return $node;
  }

  public function parseArrayExpression()
  {
    $elements = array();
    while (!$this->parser->getStream()->test(Twig_Token::OPERATOR_TYPE, ']'))
    {
      if (!empty($elements))
      {
        $this->parser->getStream()->expect(Twig_Token::OPERATOR_TYPE, ',');

        // trailing ,?
        if ($this->parser->getStream()->test(Twig_Token::OPERATOR_TYPE, ']'))
        {
          return new Twig_Node_Expression_Array($elements, $this->parser->getCurrentToken()->getLine());
        }
      }

      // hash or array element?
      if (
        $this->parser->getStream()->test(Twig_Token::STRING_TYPE)
        ||
        $this->parser->getStream()->test(Twig_Token::NUMBER_TYPE)
      )
      {
        if ($this->parser->getStream()->look()->test(Twig_Token::OPERATOR_TYPE, ':'))
        {
          // hash
          $key = $this->parser->getStream()->next()->getValue();
          $this->parser->getStream()->next();

          $elements[$key] = $this->parseExpression();

          continue;
        }
        $this->parser->getStream()->rewind();
      }

      $elements[] = $this->parseExpression();
    }

    return new Twig_Node_Expression_Array($elements, $this->parser->getCurrentToken()->getLine());
  }

  public function parsePostfixExpression($node)
  {
    $stop = false;
    while (!$stop && $this->parser->getCurrentToken()->getType() == Twig_Token::OPERATOR_TYPE)
    {
      switch ($this->parser->getCurrentToken()->getValue())
      {
        case '..':
          $node = $this->parseRangeExpression($node);
          break;

        case '.':
        case '[':
          $node = $this->parseSubscriptExpression($node);
          break;

        case '|':
          $node = $this->parseFilterExpression($node);
          break;

        default:
          $stop = true;
          break;
      }
    }

    return $node;
  }

  public function parseRangeExpression($node)
  {
    $token = $this->parser->getStream()->next();
    $lineno = $token->getLine();

    $end = $this->parseExpression();

    return new Twig_Node_Expression_Filter($node, array(array('range', array($end))), $lineno);
  }

  public function parseSubscriptExpression($node)
  {
    $token = $this->parser->getStream()->next();
    $lineno = $token->getLine();
    $arguments = array();
    if ($token->getValue() == '.')
    {
      $token = $this->parser->getStream()->next();
      if ($token->getType() == Twig_Token::NAME_TYPE || $token->getType() == Twig_Token::NUMBER_TYPE)
      {
        $arg = new Twig_Node_Expression_Constant($token->getValue(), $lineno);

        $arguments = $this->parseArguments();
      }
      else
      {
        throw new Twig_SyntaxError('Expected name or number', $lineno);
      }
    }
    else
    {
      $arg = $this->parseExpression();
      $this->parser->getStream()->expect(Twig_Token::OPERATOR_TYPE, ']');
    }

    return new Twig_Node_Expression_GetAttr($node, $arg, $arguments, $lineno, $token->getValue());
  }

  public function parseFilterExpression($node)
  {
    $lineno = $this->parser->getCurrentToken()->getLine();

    $this->parser->getStream()->next();

    return new Twig_Node_Expression_Filter($node, $this->parseFilterExpressionRaw(), $lineno);
  }

  public function parseFilterExpressionRaw()
  {
    $filters = array();
    while (true)
    {
      $token = $this->parser->getStream()->expect(Twig_Token::NAME_TYPE);

      $filters[] = array($token->getValue(), $this->parseArguments());

      if (!$this->parser->getStream()->test(Twig_Token::OPERATOR_TYPE, '|'))
      {
        break;
      }

      $this->parser->getStream()->next();
    }

    return $filters;
  }

  public function parseArguments()
  {
    if (!$this->parser->getStream()->test(Twig_Token::OPERATOR_TYPE, '('))
    {
      return array();
    }

    $args = array();
    $this->parser->getStream()->next();
    while (!$this->parser->getStream()->test(Twig_Token::OPERATOR_TYPE, ')'))
    {
      if (!empty($args))
      {
        $this->parser->getStream()->expect(Twig_Token::OPERATOR_TYPE, ',');
      }
      $args[] = $this->parseExpression();
    }
    $this->parser->getStream()->expect(Twig_Token::OPERATOR_TYPE, ')');

    return $args;
  }

  public function parseAssignmentExpression()
  {
    $lineno = $this->parser->getCurrentToken()->getLine();
    $targets = array();
    $is_multitarget = false;
    while (true)
    {
      if (!empty($targets))
      {
        $this->parser->getStream()->expect(Twig_Token::OPERATOR_TYPE, ',');
      }
      if ($this->parser->getStream()->test(Twig_Token::OPERATOR_TYPE, ')') ||
          $this->parser->getStream()->test(Twig_Token::VAR_END_TYPE) ||
          $this->parser->getStream()->test(Twig_Token::BLOCK_END_TYPE) ||
          $this->parser->getStream()->test('in'))
      {
        break;
      }
      $targets[] = $this->parsePrimaryExpression(true);
      if (!$this->parser->getStream()->test(Twig_Token::OPERATOR_TYPE, ','))
      {
        break;
      }
      $is_multitarget = true;
    }
    if (!$is_multitarget && count($targets) == 1)
    {
      return array(false, $targets[0]);
    }

    return array(true, $targets);
  }

  public function parseMultitargetExpression()
  {
    $lineno = $this->parser->getCurrentToken()->getLine();
    $targets = array();
    $is_multitarget = false;
    while (true)
    {
      if (!empty($targets))
      {
        $this->parser->getStream()->expect(Twig_Token::OPERATOR_TYPE, ',');
      }
      if ($this->parser->getStream()->test(Twig_Token::OPERATOR_TYPE, ')') ||
          $this->parser->getStream()->test(Twig_Token::VAR_END_TYPE) ||
          $this->parser->getStream()->test(Twig_Token::BLOCK_END_TYPE))
      {
        break;
      }
      $targets[] = $this->parseExpression();
      if (!$this->parser->getStream()->test(Twig_Token::OPERATOR_TYPE, ','))
      {
        break;
      }
      $is_multitarget = true;
    }
    if (!$is_multitarget && count($targets) == 1)
    {
      return array(false, $targets[0]);
    }

    return array(true, $targets);
  }
}
