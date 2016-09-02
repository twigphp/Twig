<?php

/*
 * This file is part of Twig.
 *
 * (c) 2009 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Defines a variable.
 *
 * <pre>
 *  {% type foo = 'FooClassName' %}
 *
 *  {% type foo = 'FooNamespace//FooClassName' %}
 * </pre>
 *
 * @author David Stone <david@nnucomputerwhiz.com>
 */
class Twig_TokenParser_Type extends Twig_TokenParser
{
    public function parse(Twig_Token $token)
    {
        $stream = $this->parser->getStream();

        $name = $this->parser->getStream()->expect(Twig_Token::NAME_TYPE)->getValue();
        $stream->expect(Twig_Token::OPERATOR_TYPE, '=');
        $type = $this->parser->getStream()->expect(Twig_Token::STRING_TYPE)->getValue();
        $stream->expect(Twig_Token::BLOCK_END_TYPE);

        if (0 !== strcasecmp('array', $type) && false === class_exists($type)) {
            throw new Twig_Error_Syntax(sprintf('Class "%s" not found for type set on "%s".', $type, $name), $stream->getCurrent()->getLine(), $stream->getFilename());
        }

        $this->parser->addImportedSymbol('types', $name, $type);

        return new Twig_Node_Type($name, $type, $token->getLine(), $this->getTag());
    }

    public function getTag()
    {
        return 'type';
    }
}
