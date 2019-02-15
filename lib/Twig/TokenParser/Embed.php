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
 * Embeds a template.
 */
final class Twig_TokenParser_Embed extends \Twig\TokenParser\IncludeTokenParser
{
    public function parse(\Twig\Token $token)
    {
        $stream = $this->parser->getStream();

        $parent = $this->parser->getExpressionParser()->parseExpression();

        list($variables, $only, $ignoreMissing) = $this->parseArguments();

        $parentToken = $fakeParentToken = new \Twig\Token(/* \Twig\Token::STRING_TYPE */ 7, '__parent__', $token->getLine());
        if ($parent instanceof \Twig\Node\Expression\ConstantExpression) {
            $parentToken = new \Twig\Token(/* \Twig\Token::STRING_TYPE */ 7, $parent->getAttribute('value'), $token->getLine());
        } elseif ($parent instanceof \Twig\Node\Expression\NameExpression) {
            $parentToken = new \Twig\Token(/* \Twig\Token::NAME_TYPE */ 5, $parent->getAttribute('name'), $token->getLine());
        }

        // inject a fake parent to make the parent() function work
        $stream->injectTokens([
            new \Twig\Token(/* \Twig\Token::BLOCK_START_TYPE */ 1, '', $token->getLine()),
            new \Twig\Token(/* \Twig\Token::NAME_TYPE */ 5, 'extends', $token->getLine()),
            $parentToken,
            new \Twig\Token(/* \Twig\Token::BLOCK_END_TYPE */ 3, '', $token->getLine()),
        ]);

        $module = $this->parser->parse($stream, [$this, 'decideBlockEnd'], true);

        // override the parent with the correct one
        if ($fakeParentToken === $parentToken) {
            $module->setNode('parent', $parent);
        }

        $this->parser->embedTemplate($module);

        $stream->expect(/* \Twig\Token::BLOCK_END_TYPE */ 3);

        return new \Twig\Node\EmbedNode($module->getTemplateName(), $module->getAttribute('index'), $variables, $only, $ignoreMissing, $token->getLine(), $this->getTag());
    }

    public function decideBlockEnd(\Twig\Token $token)
    {
        return $token->test('endembed');
    }

    public function getTag()
    {
        return 'embed';
    }
}

class_alias('Twig_TokenParser_Embed', 'Twig\TokenParser\EmbedTokenParser', false);
