<?php

/*
 * This file is part of Twig.
 *
 * (c) 2012 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Includes an inline template.
 */
class Twig_TokenParser_Inline extends Twig_TokenParser_Include
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
        list($variables, $only, $ignoreMissing) = $this->parseArguments();

        $module = $this->parser->parse($this->parser->getStream(), array($this, 'decideBlockEnd'), true);
        $this->parser->addInlinedTemplate($module);

        $this->parser->getStream()->expect(Twig_Token::BLOCK_END_TYPE);

        return new Twig_Node_Inline($module->getAttribute('filename'), $module->getAttribute('inline'), $variables, $only, $ignoreMissing, $token->getLine(), $this->getTag());
    }

    public function decideBlockEnd(Twig_Token $token)
    {
        return $token->test('endinline');
    }

    /**
     * Gets the tag name associated with this token parser.
     *
     * @return string The tag name
     */
    public function getTag()
    {
        return 'inline';
    }
}
