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
 * Filters a section of a template by applying filters.
 *
 *   {% filter upper %}
 *      This text becomes uppercase
 *   {% endfilter %}
 */
final class Twig_TokenParser_Filter extends \Twig\TokenParser\AbstractTokenParser
{
    public function parse(\Twig\Token $token)
    {
        $name = $this->parser->getVarName();
        $ref = new \Twig\Node\Expression\BlockReferenceExpression(new \Twig\Node\Expression\ConstantExpression($name, $token->getLine()), null, $token->getLine(), $this->getTag());

        $filter = $this->parser->getExpressionParser()->parseFilterExpressionRaw($ref, $this->getTag());
        $this->parser->getStream()->expect(/* \Twig\Token::BLOCK_END_TYPE */ 3);

        $body = $this->parser->subparse([$this, 'decideBlockEnd'], true);
        $this->parser->getStream()->expect(/* \Twig\Token::BLOCK_END_TYPE */ 3);

        $block = new \Twig\Node\BlockNode($name, $body, $token->getLine());
        $this->parser->setBlock($name, $block);

        return new \Twig\Node\PrintNode($filter, $token->getLine(), $this->getTag());
    }

    public function decideBlockEnd(\Twig\Token $token)
    {
        return $token->test('endfilter');
    }

    public function getTag()
    {
        return 'filter';
    }
}

class_alias('Twig_TokenParser_Filter', 'Twig\TokenParser\FilterTokenParser', false);
