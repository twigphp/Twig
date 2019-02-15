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
 * Imports blocks defined in another template into the current template.
 *
 *    {% extends "base.html" %}
 *
 *    {% use "blocks.html" %}
 *
 *    {% block title %}{% endblock %}
 *    {% block content %}{% endblock %}
 *
 * @see https://twig.symfony.com/doc/templates.html#horizontal-reuse for details.
 *
 * @final
 */
class Twig_TokenParser_Use extends \Twig\TokenParser\AbstractTokenParser
{
    public function parse(\Twig\Token $token)
    {
        $template = $this->parser->getExpressionParser()->parseExpression();
        $stream = $this->parser->getStream();

        if (!$template instanceof \Twig\Node\Expression\ConstantExpression) {
            throw new \Twig\Error\SyntaxError('The template references in a "use" statement must be a string.', $stream->getCurrent()->getLine(), $stream->getSourceContext());
        }

        $targets = [];
        if ($stream->nextIf('with')) {
            do {
                $name = $stream->expect(\Twig\Token::NAME_TYPE)->getValue();

                $alias = $name;
                if ($stream->nextIf('as')) {
                    $alias = $stream->expect(\Twig\Token::NAME_TYPE)->getValue();
                }

                $targets[$name] = new \Twig\Node\Expression\ConstantExpression($alias, -1);

                if (!$stream->nextIf(\Twig\Token::PUNCTUATION_TYPE, ',')) {
                    break;
                }
            } while (true);
        }

        $stream->expect(\Twig\Token::BLOCK_END_TYPE);

        $this->parser->addTrait(new \Twig\Node\Node(['template' => $template, 'targets' => new \Twig\Node\Node($targets)]));

        return new \Twig\Node\Node();
    }

    public function getTag()
    {
        return 'use';
    }
}

class_alias('Twig_TokenParser_Use', 'Twig\TokenParser\UseTokenParser', false);
