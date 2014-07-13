<?php

/*
 * This file is part of Twig.
 *
 * (c) 2010 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Enables strict mode for the given block.
 *
 * <pre>
 * {% strict %}
 *      {{ undefinedVariable }}
 * {% strict %}
 *
 * Will throw an exception when "undefinedVariable" is undefined
 * </pre>
 */
class Twig_TokenParser_Strict extends Twig_TokenParser
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

        $this->parser->getStream()->expect(Twig_Token::BLOCK_END_TYPE);
        $body = $this->parser->subparse(array($this, 'decideStrictEnd'), true);
        $this->parser->getStream()->expect(Twig_Token::BLOCK_END_TYPE);

        return new Twig_Node_Strict($body, $lineno, $this->getTag());
    }

    public function decideStrictEnd(Twig_Token $token)
    {
        return $token->test('endstrict');
    }

    /**
     * Gets the tag name associated with this token parser.
     *
     * @return string The tag name
     */
    public function getTag()
    {
        return 'strict';
    }
}
