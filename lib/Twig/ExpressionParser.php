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
        while ($this->parser->getStream()->test(Twig_Token::OPERATOR_TYPE, '?')) {
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
        while ($this->parser->getStream()->test('or')) {
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
        while ($this->parser->getStream()->test('and')) {
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
        ) {
            $ops[] = new Twig_Node_Expression_Constant($this->parser->getStream()->next()->getValue(), $lineno);
            $ops[] = $this->parseAddExpression();
        }

        if (empty($ops)) {
            return $expr;
        }

        return new Twig_Node_Expression_Compare($expr, new Twig_Node($ops), $lineno);
    }

    public function parseAddExpression()
    {
        $lineno = $this->parser->getCurrentToken()->getLine();
        $left = $this->parseSubExpression();
        while ($this->parser->getStream()->test(Twig_Token::OPERATOR_TYPE, '+')) {
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
        while ($this->parser->getStream()->test(Twig_Token::OPERATOR_TYPE, '-')) {
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
        while ($this->parser->getStream()->test(Twig_Token::OPERATOR_TYPE, '~')) {
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
        while ($this->parser->getStream()->test(Twig_Token::OPERATOR_TYPE, '*')) {
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
        while ($this->parser->getStream()->test(Twig_Token::OPERATOR_TYPE, '/')) {
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
        while ($this->parser->getStream()->test(Twig_Token::OPERATOR_TYPE, '//')) {
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
        while ($this->parser->getStream()->test(Twig_Token::OPERATOR_TYPE, '%')) {
            $this->parser->getStream()->next();
            $right = $this->parseUnaryExpression();
            $left = new Twig_Node_Expression_Binary_Mod($left, $right, $lineno);
            $lineno = $this->parser->getCurrentToken()->getLine();
        }

        return $left;
    }

    public function parseUnaryExpression()
    {
        if ($this->parser->getStream()->test('not')) {
            return $this->parseNotExpression();
        }
        if ($this->parser->getCurrentToken()->getType() == Twig_Token::OPERATOR_TYPE) {
            switch ($this->parser->getCurrentToken()->getValue()) {
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
        switch ($token->getType()) {
            case Twig_Token::NAME_TYPE:
                $this->parser->getStream()->next();
                switch ($token->getValue()) {
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
                if ($token->test(Twig_Token::OPERATOR_TYPE, '[')) {
                    $node = $this->parseArrayExpression();
                } elseif ($token->test(Twig_Token::OPERATOR_TYPE, '(')) {
                    $this->parser->getStream()->next();
                    $node = $this->parseExpression();
                    $this->parser->getStream()->expect(Twig_Token::OPERATOR_TYPE, ')');
                } else {
                    throw new Twig_SyntaxError(sprintf('Unexpected token "%s" of value "%s"', Twig_Token::getTypeAsString($token->getType()), $token->getValue()), $token->getLine());
                }
        }

        if (!$assignment) {
            $node = $this->parsePostfixExpression($node);
        }

        return $node;
    }

    public function parseArrayExpression()
    {
        $stream = $this->parser->getStream();
        $stream->expect(Twig_Token::OPERATOR_TYPE, '[');
        $elements = array();
        while (!$stream->test(Twig_Token::OPERATOR_TYPE, ']')) {
            if (!empty($elements)) {
                $stream->expect(Twig_Token::OPERATOR_TYPE, ',');

                // trailing ,?
                if ($stream->test(Twig_Token::OPERATOR_TYPE, ']')) {
                    $stream->expect(Twig_Token::OPERATOR_TYPE, ']');

                    return new Twig_Node_Expression_Array($elements, $this->parser->getCurrentToken()->getLine());
                }
            }

            // hash or array element?
            if (
                $stream->test(Twig_Token::STRING_TYPE)
                ||
                $stream->test(Twig_Token::NUMBER_TYPE)
            )
            {
                if ($stream->look()->test(Twig_Token::OPERATOR_TYPE, ':')) {
                    // hash
                    $key = $stream->next()->getValue();
                    $stream->next();

                    $elements[$key] = $this->parseExpression();

                    continue;
                }
                $stream->rewind();
            }

            $elements[] = $this->parseExpression();
        }
        $stream->expect(Twig_Token::OPERATOR_TYPE, ']');

        return new Twig_Node_Expression_Array($elements, $this->parser->getCurrentToken()->getLine());
    }

    public function parsePostfixExpression($node)
    {
        while (1) {
            $token = $this->parser->getCurrentToken();
            if ($token->getType() == Twig_Token::OPERATOR_TYPE) {
                if ('..' == $token->getValue()) {
                    $node = $this->parseRangeExpression($node);
                } elseif ('.' == $token->getValue() || '[' == $token->getValue()) {
                    $node = $this->parseSubscriptExpression($node);
                } elseif ('|' == $token->getValue()) {
                    $node = $this->parseFilterExpression($node);
                } else {
                    break;
                }
            } elseif ($token->getType() == Twig_Token::NAME_TYPE && 'is' == $token->getValue()) {
                $node = $this->parseTestExpression($node);
                break;
            } else {
                break;
            }
        }

        return $node;
    }

    public function parseTestExpression($node)
    {
        $stream = $this->parser->getStream();
        $token = $stream->next();
        $lineno = $token->getLine();

        $negated = false;
        if ($stream->test('not')) {
            $stream->next();
            $negated = true;
        }

        $name = $stream->expect(Twig_Token::NAME_TYPE);

        $arguments = null;
        if ($stream->test(Twig_Token::OPERATOR_TYPE, '(')) {
            $arguments = $this->parseArguments($node);
        }
        $test = new Twig_Node_Expression_Test($node, $name->getValue(), $arguments, $lineno);

        if ($negated) {
            $test = new Twig_Node_Expression_Unary_Not($test, $lineno);
        }

        return $test;
    }

    public function parseRangeExpression($node)
    {
        $token = $this->parser->getStream()->next();
        $lineno = $token->getLine();

        $end = $this->parseExpression();

        $filters = new Twig_Node(array(new Twig_Node_Expression_Constant('range', $lineno), new Twig_Node(array($end))));

        return new Twig_Node_Expression_Filter($node, $filters, $lineno);
    }

    public function parseSubscriptExpression($node)
    {
        $token = $this->parser->getStream()->next();
        $lineno = $token->getLine();
        $arguments = new Twig_Node();
        $type = Twig_Node_Expression_GetAttr::TYPE_ANY;
        if ($token->getValue() == '.') {
            $token = $this->parser->getStream()->next();
            if ($token->getType() == Twig_Token::NAME_TYPE || $token->getType() == Twig_Token::NUMBER_TYPE) {
                $arg = new Twig_Node_Expression_Constant($token->getValue(), $lineno);

                if ($this->parser->getStream()->test(Twig_Token::OPERATOR_TYPE, '(')) {
                    $type = Twig_Node_Expression_GetAttr::TYPE_METHOD;
                    $arguments = $this->parseArguments();
                } else {
                    $arguments = new Twig_Node();
                }
            } else {
                throw new Twig_SyntaxError('Expected name or number', $lineno);
            }
        } else {
            $type = Twig_Node_Expression_GetAttr::TYPE_ARRAY;

            $arg = $this->parseExpression();
            $this->parser->getStream()->expect(Twig_Token::OPERATOR_TYPE, ']');
        }

        return new Twig_Node_Expression_GetAttr($node, $arg, $arguments, $type, $lineno);
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
        while (true) {
            $token = $this->parser->getStream()->expect(Twig_Token::NAME_TYPE);

            $filters[] = new Twig_Node_Expression_Constant($token->getValue(), $token->getLine());
            if (!$this->parser->getStream()->test(Twig_Token::OPERATOR_TYPE, '(')) {
                $filters[] = new Twig_Node();
            } else {
                $filters[] = $this->parseArguments();
            }

            if (!$this->parser->getStream()->test(Twig_Token::OPERATOR_TYPE, '|')) {
                break;
            }

            $this->parser->getStream()->next();
        }

        return new Twig_Node($filters);
    }

    public function parseArguments()
    {
        $parser = $this->parser->getStream();
        $parser->expect(Twig_Token::OPERATOR_TYPE, '(');

        $args = array();
        while (!$parser->test(Twig_Token::OPERATOR_TYPE, ')')) {
            if (!empty($args)) {
                $parser->expect(Twig_Token::OPERATOR_TYPE, ',');
            }
            $args[] = $this->parseExpression();
        }
        $parser->expect(Twig_Token::OPERATOR_TYPE, ')');

        return new Twig_Node($args);
    }

    public function parseAssignmentExpression()
    {
        $lineno = $this->parser->getCurrentToken()->getLine();
        $targets = array();
        while (true) {
            if (!empty($targets)) {
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
            if (!$this->parser->getStream()->test(Twig_Token::OPERATOR_TYPE, ',')) {
                break;
            }
        }

        return new Twig_Node($targets);
    }

    public function parseMultitargetExpression()
    {
        $lineno = $this->parser->getCurrentToken()->getLine();
        $targets = array();
        $is_multitarget = false;
        while (true) {
            if (!empty($targets)) {
                $this->parser->getStream()->expect(Twig_Token::OPERATOR_TYPE, ',');
            }
            if ($this->parser->getStream()->test(Twig_Token::OPERATOR_TYPE, ')') ||
                    $this->parser->getStream()->test(Twig_Token::VAR_END_TYPE) ||
                    $this->parser->getStream()->test(Twig_Token::BLOCK_END_TYPE))
            {
                break;
            }
            $targets[] = $this->parseExpression();
            if (!$this->parser->getStream()->test(Twig_Token::OPERATOR_TYPE, ',')) {
                break;
            }
            $is_multitarget = true;
        }

        return array($is_multitarget, new Twig_Node($targets));
    }
}
