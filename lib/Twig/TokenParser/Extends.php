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
class Twig_TokenParser_Extends extends Twig_TokenParser
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
        if (null !== $this->parser->getParent()) {
            throw new Twig_SyntaxError('Multiple extends tags are forbidden', $token->getLine());
        }
        $this->parser->setParent($this->parser->getExpressionParser()->parseExpression());

        $this->parser->getStream()->expect(Twig_Token::BLOCK_END_TYPE);

        return null;
    }

    /**
     * Gets the tag name associated with this token parser.
     *
     * @param string The tag name
     */
    public function getTag()
    {
        return 'extends';
    }
}
