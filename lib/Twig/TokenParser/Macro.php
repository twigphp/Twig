<?php

/*
 * This file is part of Twig.
 *
 * (c) 2009 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Twig_TokenParser_Macro extends Twig_TokenParser
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
        $name = $this->parser->getStream()->expect(Twig_Token::NAME_TYPE)->getValue();

        $arguments = $this->parser->getExpressionParser()->parseArguments();

        $this->parser->getStream()->expect(Twig_Token::BLOCK_END_TYPE);
        $this->parser->pushLocalScope();
        $body = $this->parser->subparse(array($this, 'decideBlockEnd'), true);
        if ($this->parser->getStream()->test(Twig_Token::NAME_TYPE)) {
            $value = $this->parser->getStream()->next()->getValue();

            if ($value != $name) {
                throw new Twig_Error_Syntax(sprintf("Expected endmacro for macro '$name' (but %s given)", $value), $lineno);
            }
        }
        $this->parser->popLocalScope();
        $this->parser->getStream()->expect(Twig_Token::BLOCK_END_TYPE);

        $this->parser->setMacro($name, new Twig_Node_Macro($name, $body, $arguments, $lineno, $this->getTag()));

        return null;
    }

    public function decideBlockEnd(Twig_Token $token)
    {
        return $token->test('endmacro');
    }

    /**
     * Gets the tag name associated with this token parser.
     *
     * @param string The tag name
     */
    public function getTag()
    {
        return 'macro';
    }
}
