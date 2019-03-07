<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\TokenParser;

use Twig\Node\BlockNode;
use Twig\Node\Expression\BlockReferenceExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\PrintNode;
use Twig\Token;

/**
 * Remove whitespaces between HTML tags.
 *
 *   {% spaceless %}
 *      <div>
 *          <strong>foo</strong>
 *      </div>
 *   {% endspaceless %}
 *   {# output will be <div><strong>foo</strong></div> #}
 *
 * @final
 */
class SpacelessTokenParser extends AbstractTokenParser
{
    public function parse(Token $token)
    {
        $this->parser->getStream()->injectTokens([
            new Token(Token::NAME_TYPE, 'spaceless', $token->getLine()),
        ]);

        $name = $this->parser->getVarName();
        $ref = new BlockReferenceExpression(new ConstantExpression($name, $token->getLine()), null, $token->getLine(), $this->getTag());

        $filter = $this->parser->getExpressionParser()->parseFilterExpressionRaw($ref, $this->getTag());
        $this->parser->getStream()->expect(Token::BLOCK_END_TYPE);

        $body = $this->parser->subparse([$this, 'decideSpacelessEnd'], true);
        $this->parser->getStream()->expect(Token::BLOCK_END_TYPE);

        $block = new BlockNode($name, $body, $token->getLine());
        $this->parser->setBlock($name, $block);

        return new PrintNode($filter, $token->getLine(), $this->getTag());
    }

    public function decideSpacelessEnd(Token $token)
    {
        return $token->test('endspaceless');
    }

    public function getTag()
    {
        return 'spaceless';
    }
}

class_alias('Twig\TokenParser\SpacelessTokenParser', 'Twig_TokenParser_Spaceless');
