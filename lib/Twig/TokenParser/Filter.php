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
    /**
     * Parses a token and returns a node.
     *
     * @param Twig_Token $token A Twig_Token instance
     *
     * @return Twig_NodeInterface A Twig_NodeInterface instance
     */
    public function parse(Twig_Token $token)
    {
        $filters = $this->parser->getExpressionParser()->parseFilterExpressionRaw();

        $this->parser->getStream()->expect(Twig_Token::BLOCK_END_TYPE);
        $body = $this->parser->subparse(array($this, 'decideBlockEnd'), true);
        $this->parser->getStream()->expect(Twig_Token::BLOCK_END_TYPE);

        $name = '_tmp'.rand(10000, 99999);
        $ref = new Twig_Node_BlockReference($name, $token->getLine(), $this->getTag());

        $block = new Twig_Node_Block($name, $body, $token->getLine());
        $this->parser->setBlock($name, $block);

        $set = new Twig_Node_Set(true, new Twig_Node(array(new Twig_Node_Expression_AssignName($name, $token->getLine()))), new Twig_Node(array($ref)), $token->getLine(), $this->getTag());
        $filter = new Twig_Node_Expression_Filter(new Twig_Node_Expression_Name($name, $token->getLine()), $filters, $token->getLine(), $this->getTag());
        $filter = new Twig_Node_Print($filter, $token->getLine(), $this->getTag());

        return new Twig_Node(array($set, $filter));
    }

    public function decideBlockEnd($token)
    {
        return $token->test('endfilter');
    }

    /**
     * Gets the tag name associated with this token parser.
     *
     * @param string The tag name
     */
    public function getTag()
    {
        return 'filter';
    }
}
