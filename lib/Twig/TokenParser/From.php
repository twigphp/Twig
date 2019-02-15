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
 * Imports macros.
 *
 *   {% from 'forms.html' import forms %}
 */
final class Twig_TokenParser_From extends \Twig\TokenParser\AbstractTokenParser
{
    public function parse(\Twig\Token $token)
    {
        $macro = $this->parser->getExpressionParser()->parseExpression();
        $stream = $this->parser->getStream();
        $stream->expect('import');

        $targets = [];
        do {
            $name = $stream->expect(/* \Twig\Token::NAME_TYPE */ 5)->getValue();

            $alias = $name;
            if ($stream->nextIf('as')) {
                $alias = $stream->expect(/* \Twig\Token::NAME_TYPE */ 5)->getValue();
            }

            $targets[$name] = $alias;

            if (!$stream->nextIf(/* \Twig\Token::PUNCTUATION_TYPE */ 9, ',')) {
                break;
            }
        } while (true);

        $stream->expect(/* \Twig\Token::BLOCK_END_TYPE */ 3);

        $node = new \Twig\Node\ImportNode($macro, new \Twig\Node\Expression\AssignNameExpression($this->parser->getVarName(), $token->getLine()), $token->getLine(), $this->getTag());

        foreach ($targets as $name => $alias) {
            $this->parser->addImportedSymbol('function', $alias, 'macro_'.$name, $node->getNode('var'));
        }

        return $node;
    }

    public function getTag()
    {
        return 'from';
    }
}

class_alias('Twig_TokenParser_From', 'Twig\TokenParser\FromTokenParser', false);
