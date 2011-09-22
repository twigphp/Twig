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
 * Remove whitespaces between HTML tags.
 *
 * <pre>
 * {% spaceless html %} is equivalent to {% spaceless %}
 * 
 * {% spaceless %}
 *      <div>
 *          <strong>foo</strong>
 *      </div>
 * {% endspaceless %}
 *
 * {# output will be <div><strong>foo</strong></div> #}
 * 
 * {% spaceless json %}
 *      {
 *          "foo":"bar"
 *      }
 * {% endspaceless %}
 *
 * {# output will be {"foo":"bar"} #}
 * 
 * </pre>
 */
class Twig_TokenParser_Spaceless extends Twig_TokenParser
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
        
        $type = 'html';
        if ($this->parser->getStream()->test(Twig_Token::NAME_TYPE)) {
	          $type = $this->parser->getStream()->next()->getValue();
	      }
	      if (!in_array($type, array('html', 'json'))) {
            throw new Twig_Error_Syntax("Spaceless value must be 'html' or 'json'", $lineno);
        }
        
        $this->parser->getStream()->expect(Twig_Token::BLOCK_END_TYPE);
        $body = $this->parser->subparse(array($this, 'decideSpacelessEnd'), true);
        $this->parser->getStream()->expect(Twig_Token::BLOCK_END_TYPE);

        return new Twig_Node_Spaceless($type, $body, $lineno, $this->getTag());
    }

    public function decideSpacelessEnd(Twig_Token $token)
    {
        return $token->test('endspaceless');
    }

    /**
     * Gets the tag name associated with this token parser.
     *
     * @param string The tag name
     */
    public function getTag()
    {
        return 'spaceless';
    }
}
