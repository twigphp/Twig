<?php

/*
 * This file is part of Twig.
 *
 * (c) 2009 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Twig_TokenParser_AutoEscape extends Twig_TokenParser
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
        $value = $this->parser->getStream()->expect(Twig_Token::NAME_TYPE)->getValue();
        if (!in_array($value, array('on', 'off'))) {
            throw new Twig_SyntaxError("Autoescape value must be 'on' or 'off'", $lineno);
        }
        $value = 'on' === $value ? true : false;

        if ($this->parser->getStream()->test(Twig_Token::NAME_TYPE)) {
            if (false === $value) {
                throw new Twig_SyntaxError(sprintf('Unexpected escaping strategy as you set autoescaping to off.', $lineno), -1);
            }

            $value = $this->parser->getStream()->next()->getValue();
        }

        $this->parser->getStream()->expect(Twig_Token::BLOCK_END_TYPE);
        $body = $this->parser->subparse(array($this, 'decideBlockEnd'), true);
        $this->parser->getStream()->expect(Twig_Token::BLOCK_END_TYPE);

        return new Twig_Node_AutoEscape($value, $body, $lineno, $this->getTag());
    }

    public function decideBlockEnd($token)
    {
        return $token->test('endautoescape');
    }

    /**
     * Gets the tag name associated with this token parser.
     *
     * @param string The tag name
     */
    public function getTag()
    {
        return 'autoescape';
    }
}
