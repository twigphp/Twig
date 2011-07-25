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
 * Includes a template.
 *
 * <pre>
 *   {% include 'header.html' %}
 *     Body
 *   {% include 'footer.html' %}
 * </pre>
 */
class Twig_TokenParser_Include extends Twig_TokenParser
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
        $expr = $this->parser->getExpressionParser()->parseExpression();

        $variables = null;
        if ($this->parser->getStream()->test(Twig_Token::NAME_TYPE, 'with')) {
            $this->parser->getStream()->next();

            $variables = $this->parser->getExpressionParser()->parseExpression();
        }

        $only = false;
        if ($this->parser->getStream()->test(Twig_Token::NAME_TYPE, 'only')) {
            $this->parser->getStream()->next();

            $only = true;
        }

        $this->parser->getStream()->expect(Twig_Token::BLOCK_END_TYPE);

        return new Twig_Node_Include($expr, $variables, $only, $token->getLine(), $this->getTag());
    }

    /**
     * Gets the tag name associated with this token parser.
     *
     * @param string The tag name
     */
    public function getTag()
    {
        return 'include';
    }
}
