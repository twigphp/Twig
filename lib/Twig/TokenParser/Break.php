<?php
/*
 * This file is part of Twig.
 *
 * (c) 2012 Badlee Oshimin
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * breaking a Loop.
 *
 * <pre>
 * <i>Show letter a to n</i>
 * <ul>
 *  {% for i in 'a'..'z' %}
 *    <li>{{ i }}</li>
 *    {% if i == 'n'%}
 *      {% break %}
 *    {% endif %}
 *  {% endfor %}
 * </ul>
 * </pre>
 */
class Twig_TokenParser_Break extends Twig_TokenParser
{
    /**
     * Gets the tag name associated with this token parser.
     *
     * @return string The tag name
     */
    public function getTag()
    {
        return 'break';
    }
    
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
        return new Twig_Node_Break($lineno,  $this->getTag());
    }
}