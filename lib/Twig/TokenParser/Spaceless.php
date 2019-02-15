<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Remove whitespaces between HTML tags.
 *
 *   {% spaceless %}
 *      <div>
 *          <strong>foo</strong>
 *      </div>
 *   {% endspaceless %}
 *   {# output will be <div><strong>foo</strong></div> #}
 */
final class Twig_TokenParser_Spaceless extends \Twig\TokenParser\AbstractTokenParser
{
    public function parse(\Twig\Token $token)
    {
        $lineno = $token->getLine();

        $this->parser->getStream()->expect(/* \Twig\Token::BLOCK_END_TYPE */ 3);
        $body = $this->parser->subparse([$this, 'decideSpacelessEnd'], true);
        $this->parser->getStream()->expect(/* \Twig\Token::BLOCK_END_TYPE */ 3);

        return new \Twig\Node\SpacelessNode($body, $lineno, $this->getTag());
    }

    public function decideSpacelessEnd(\Twig\Token $token)
    {
        return $token->test('endspaceless');
    }

    public function getTag()
    {
        return 'spaceless';
    }
}

class_alias('Twig_TokenParser_Spaceless', 'Twig\TokenParser\SpacelessTokenParser', false);
