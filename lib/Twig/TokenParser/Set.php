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
    /**
     * Parses a token and returns a node.
     *
     * @param Twig_Token $token A Twig_Token instance
     *
     * @return Twig_NodeInterface A Twig_NodeInterface instance
     */
    public function parse(Twig_Token $token)
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();
        $names = $this->parser->getExpressionParser()->parseAssignmentExpression();

        $capture = false;
        if ($stream->test(Twig_Token::OPERATOR_TYPE, '=')) {
            $stream->next();
            list(, $values) = $this->parser->getExpressionParser()->parseMultitargetExpression();

            $stream->expect(Twig_Token::BLOCK_END_TYPE);

            if (count($names) !== count($values)) {
                throw new Twig_SyntaxError("When using set, you must have the same number of variables and assignements.", $lineno);
            }
        } else {
            $capture = true;

            if (count($names) > 1) {
                throw new Twig_SyntaxError("When using set with a block, you cannot have a multi-target.", $lineno);
            }

            $stream->expect(Twig_Token::BLOCK_END_TYPE);

            $values = $this->parser->subparse(array($this, 'decideBlockEnd'), true);
            $stream->expect(Twig_Token::BLOCK_END_TYPE);
        }

        return new Twig_Node_Set($capture, $names, $values, $lineno, $this->getTag());
    }

    public function decideBlockEnd($token)
    {
        return $token->test('endset');
    }

    /**
     * Gets the tag name associated with this token parser.
     *
     * @param string The tag name
     */
    public function getTag()
    {
        return 'set';
    }
}
