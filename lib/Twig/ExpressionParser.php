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
 * This parser implements a "Precedence climbing" algorithm.
 *
 * @see http://www.engr.mun.ca/~theo/Misc/exp_parsing.htm
 * @see http://en.wikipedia.org/wiki/Operator-precedence_parser
 *
 * @package    twig
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 */
class Twig_ExpressionParser
{
    const OPERATOR_LEFT = 1;
    const OPERATOR_RIGHT = 2;

    protected $parser;
    protected $unaryOperators;
    protected $binaryOperators;

    public function __construct(Twig_Parser $parser, array $unaryOperators, array $binaryOperators)
    {
        $this->parser = $parser;
        $this->unaryOperators = $unaryOperators;
        $this->binaryOperators = $binaryOperators;
    }

    public function parseExpression($precedence = 0)
    {
        $expr = $this->getPrimary();
        $token = $this->parser->getCurrentToken();
        while ($this->isBinary($token) && $this->binaryOperators[$token->getValue()]['precedence'] >= $precedence) {
            $op = $this->binaryOperators[$token->getValue()];
            $this->parser->getStream()->next();

            if (isset($op['callable'])) {
                $expr = call_user_func($op['callable'], $this->parser, $expr);
            } else {
                $expr1 = $this->parseExpression(self::OPERATOR_LEFT === $op['associativity'] ? $op['precedence'] + 1 : $op['precedence']);
                $class = $op['class'];
                $expr = new $class($expr, $expr1, $token->getLine());
            }

            $token = $this->parser->getCurrentToken();
        }

        if (0 === $precedence) {
            return $this->parseConditionalExpression($expr);
        }

        return $expr;
    }

    protected function getPrimary()
    {
        $token = $this->parser->getCurrentToken();

        if ($this->isUnary($token)) {
            $operator = $this->unaryOperators[$token->getValue()];
            $this->parser->getStream()->next();
            $expr = $this->parseExpression($operator['precedence']);
            $class = $operator['class'];

            return $this->parsePostfixExpression(new $class($expr, $token->getLine()));
        } elseif ($token->test(Twig_Token::PUNCTUATION_TYPE, '(')) {
            $this->parser->getStream()->next();
            $expr = $this->parseExpression();
            $this->parser->getStream()->expect(Twig_Token::PUNCTUATION_TYPE, ')', 'An opened parenthesis is not properly closed');

            return $this->parsePostfixExpression($expr);
        }

        return $this->parsePrimaryExpression();
    }

    protected function parseConditionalExpression($expr)
    {
        while ($this->parser->getStream()->test(Twig_Token::PUNCTUATION_TYPE, '?')) {
            $this->parser->getStream()->next();
            $expr2 = $this->parseExpression();
            $this->parser->getStream()->expect(Twig_Token::PUNCTUATION_TYPE, ':', 'The ternary operator must have a default value');
            $expr3 = $this->parseExpression();

            $expr = new Twig_Node_Expression_Conditional($expr, $expr2, $expr3, $this->parser->getCurrentToken()->getLine());
        }

        return $expr;
    }

    protected function isUnary(Twig_Token $token)
    {
        return $this->parser->getStream()->test(Twig_Token::OPERATOR_TYPE) && isset($this->unaryOperators[$token->getValue()]);
    }

    protected function isBinary(Twig_Token $token)
    {
        return $this->parser->getStream()->test(Twig_Token::OPERATOR_TYPE) && isset($this->binaryOperators[$token->getValue()]);
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
                if ($token->test(Twig_Token::PUNCTUATION_TYPE, '[')) {
                    $node = $this->parseArrayExpression();
                } elseif ($token->test(Twig_Token::PUNCTUATION_TYPE, '{')) {
                    $node = $this->parseHashExpression();
                } else {
                    throw new Twig_Error_Syntax(sprintf('Unexpected token "%s" of value "%s"', Twig_Token::getTypeAsString($token->getType()), $token->getValue()), $token->getLine());
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
        $stream->expect(Twig_Token::PUNCTUATION_TYPE, '[', 'An array element was expected');
        $elements = array();
        while (!$stream->test(Twig_Token::PUNCTUATION_TYPE, ']')) {
            if (!empty($elements)) {
                $stream->expect(Twig_Token::PUNCTUATION_TYPE, ',', 'An array element must be followed by a comma (,)');

                // trailing ,?
                if ($stream->test(Twig_Token::PUNCTUATION_TYPE, ']')) {
                    break;
                }
            }

            $elements[] = $this->parseExpression();
        }
        $stream->expect(Twig_Token::PUNCTUATION_TYPE, ']', 'An opened array is not properly closed');

        return new Twig_Node_Expression_Array($elements, $stream->getCurrent()->getLine());
    }

    public function parseHashExpression()
    {
        $stream = $this->parser->getStream();
        $stream->expect(Twig_Token::PUNCTUATION_TYPE, '{', 'A hash element was expected');
        $elements = array();
        while (!$stream->test(Twig_Token::PUNCTUATION_TYPE, '}')) {
            if (!empty($elements)) {
                $stream->expect(Twig_Token::PUNCTUATION_TYPE, ',', 'A hash value must be followed by a comma (,)');

                // trailing ,?
                if ($stream->test(Twig_Token::PUNCTUATION_TYPE, '}')) {
                    break;
                }
            }

            if (!$stream->test(Twig_Token::STRING_TYPE) && !$stream->test(Twig_Token::NUMBER_TYPE)) {
                throw new Twig_Error_Syntax(sprintf('A hash key must be a quoted string or a number (unexpected token "%s" of value "%s"', Twig_Token::getTypeAsString($stream->getCurrent()->getType()), $stream->getCurrent()->getValue()), $stream->getCurrent()->getLine());
            }

            $key = $stream->next()->getValue();
            $stream->expect(Twig_Token::PUNCTUATION_TYPE, ':', 'A hash key must be followed by a colon (:)');
            $elements[$key] = $this->parseExpression();
        }
        $stream->expect(Twig_Token::PUNCTUATION_TYPE, '}', 'An opened hash is not properly closed');

        return new Twig_Node_Expression_Array($elements, $stream->getCurrent()->getLine());
    }

    public function parsePostfixExpression($node)
    {
        while (1) {
            $token = $this->parser->getCurrentToken();
            if ($token->getType() == Twig_Token::PUNCTUATION_TYPE) {
                if ('.' == $token->getValue() || '[' == $token->getValue()) {
                    $node = $this->parseSubscriptExpression($node);
                } elseif ('|' == $token->getValue()) {
                    $node = $this->parseFilterExpression($node);
                } else {
                    break;
                }
            } else {
                break;
            }
        }

        return $node;
    }

    public function parseSubscriptExpression($node)
    {
        $token = $this->parser->getStream()->next();
        $lineno = $token->getLine();
        $arguments = new Twig_Node();
        $type = Twig_Node_Expression_GetAttr::TYPE_ANY;
        if ($token->getValue() == '.') {
            $token = $this->parser->getStream()->next();
            if (
                $token->getType() == Twig_Token::NAME_TYPE
                ||
                $token->getType() == Twig_Token::NUMBER_TYPE
                ||
                ($token->getType() == Twig_Token::OPERATOR_TYPE && preg_match(Twig_Lexer::REGEX_NAME, $token->getValue()))
            ) {
                $arg = new Twig_Node_Expression_Constant($token->getValue(), $lineno);

                if ($this->parser->getStream()->test(Twig_Token::PUNCTUATION_TYPE, '(')) {
                    $type = Twig_Node_Expression_GetAttr::TYPE_METHOD;
                    $arguments = $this->parseArguments();
                } else {
                    $arguments = new Twig_Node();
                }
            } else {
                throw new Twig_Error_Syntax('Expected name or number', $lineno);
            }
        } else {
            $type = Twig_Node_Expression_GetAttr::TYPE_ARRAY;

            $arg = $this->parseExpression();
            $this->parser->getStream()->expect(Twig_Token::PUNCTUATION_TYPE, ']');
        }

        return new Twig_Node_Expression_GetAttr($node, $arg, $arguments, $type, $lineno);
    }

    public function parseFilterExpression($node)
    {
        $this->parser->getStream()->next();

        return $this->parseFilterExpressionRaw($node);
    }

    public function parseFilterExpressionRaw($node, $tag = null)
    {
        $lineno = $this->parser->getCurrentToken()->getLine();

        while (true) {
            $token = $this->parser->getStream()->expect(Twig_Token::NAME_TYPE);

            $name = new Twig_Node_Expression_Constant($token->getValue(), $token->getLine());
            if (!$this->parser->getStream()->test(Twig_Token::PUNCTUATION_TYPE, '(')) {
                $arguments = new Twig_Node();
            } else {
                $arguments = $this->parseArguments();
            }

            $node = new Twig_Node_Expression_Filter($node, $name, $arguments, $token->getLine(), $tag);

            if (!$this->parser->getStream()->test(Twig_Token::PUNCTUATION_TYPE, '|')) {
                break;
            }

            $this->parser->getStream()->next();
        }

        return $node;
    }

    public function parseArguments()
    {
        $parser = $this->parser->getStream();
        $parser->expect(Twig_Token::PUNCTUATION_TYPE, '(');

        $args = array();
        while (!$parser->test(Twig_Token::PUNCTUATION_TYPE, ')')) {
            if (!empty($args)) {
                $parser->expect(Twig_Token::PUNCTUATION_TYPE, ',', 'Arguments must be separated by a comma (,)');
            }
            $args[] = $this->parseExpression();
        }
        $parser->expect(Twig_Token::PUNCTUATION_TYPE, ')', 'A list of arguments must be closed by a parenthesis');

        return new Twig_Node($args);
    }

    public function parseAssignmentExpression()
    {
        $lineno = $this->parser->getCurrentToken()->getLine();
        $targets = array();
        while (true) {
            if (!empty($targets)) {
                $this->parser->getStream()->expect(Twig_Token::PUNCTUATION_TYPE, ',', 'Multiple assignments must be separated by a comma (,)');
            }
            if ($this->parser->getStream()->test(Twig_Token::PUNCTUATION_TYPE, ')') ||
                    $this->parser->getStream()->test(Twig_Token::VAR_END_TYPE) ||
                    $this->parser->getStream()->test(Twig_Token::BLOCK_END_TYPE))
            {
                break;
            }
            $targets[] = $this->parsePrimaryExpression(true);
            if (!$this->parser->getStream()->test(Twig_Token::PUNCTUATION_TYPE, ',')) {
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
                $this->parser->getStream()->expect(Twig_Token::PUNCTUATION_TYPE, ',', 'Multiple assignments must be separated by a comma (,)');
            }
            if ($this->parser->getStream()->test(Twig_Token::PUNCTUATION_TYPE, ')') ||
                    $this->parser->getStream()->test(Twig_Token::VAR_END_TYPE) ||
                    $this->parser->getStream()->test(Twig_Token::BLOCK_END_TYPE))
            {
                break;
            }
            $targets[] = $this->parseExpression();
            if (!$this->parser->getStream()->test(Twig_Token::PUNCTUATION_TYPE, ',')) {
                break;
            }
            $is_multitarget = true;
        }

        return array($is_multitarget, new Twig_Node($targets));
    }
}
