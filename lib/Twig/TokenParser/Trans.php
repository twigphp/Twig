<?php

/*
 * This file is part of Twig.
 *
 * (c) 2010 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Twig_TokenParser_Trans extends Twig_TokenParser
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
        $count = null;
        if (!$stream->test(Twig_Token::BLOCK_END_TYPE)) {
            $count = new Twig_Node_Expression_Name($stream->expect(Twig_Token::NAME_TYPE)->getValue(), $lineno);
        }

        $stream->expect(Twig_Token::BLOCK_END_TYPE);
        $body = $this->parser->subparse(array($this, 'decideForFork'));
        $plural = null;
        if ('plural' === $stream->next()->getValue()) {
            $stream->expect(Twig_Token::BLOCK_END_TYPE);
            $plural = $this->parser->subparse(array($this, 'decideForEnd'), true);

            if (null === $count) {
                throw new Twig_SyntaxError('When a plural is used, you must pass the count as an argument to the "trans" tag', $lineno);
            }
        }
        $stream->expect(Twig_Token::BLOCK_END_TYPE);

        return new Twig_Node_Trans($count, $body, $plural, $lineno, $this->getTag());
    }

    public function decideForFork($token)
    {
        return $token->test(array('plural', 'endtrans'));
    }

    public function decideForEnd($token)
    {
        return $token->test('endtrans');
    }

    /**
     * Gets the tag name associated with this token parser.
     *
     * @param string The tag name
     */
    public function getTag()
    {
        return 'trans';
    }
}
