<?php

/*
 * This file is part of Twig.
 *
 * (c) 2009 Fabien Potencier
 * (c) 2013 Berny Cantos
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Enforces a nested scope
 *
 * <pre>
 * {% set foo = 'Hello', separator = ', ', bar = 'Twig' %}
 * {% with foo = 'Goodbye' %} {# Optional setter #}
 *     {% set bar = 'World' %}
 *     {{ foo ~ separator ~ bar }} {# This should print "Goodbye, World" #}
 * {% endwith %}
 * {{ foo ~ separator ~ bar }} {# This should print "Hello, Twig" #}
 * </pre>
 *
 * @author Berny Cantos <be@rny.cc>
 */
class Twig_TokenParser_With extends Twig_TokenParser
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

        if ($stream->test(Twig_Token::BLOCK_END_TYPE) === false) {
            $names = $this->parser->getExpressionParser()->parseAssignmentExpression();
            $stream->expect(Twig_Token::OPERATOR_TYPE, '=');
            $values = $this->parser->getExpressionParser()->parseMultitargetExpression();

            if (count($names) !== count($values)) {
                throw new Twig_Error_Syntax("When using set, you must have the same number of variables and assignments.", $stream->getCurrent()->getLine(), $stream->getFilename());
            }

            $setter = new Twig_Node_Set(false, $names, $values, $lineno);
        } else {
            $setter = new Twig_Node();
        }

        $stream->expect(Twig_Token::BLOCK_END_TYPE);
        $body = $this->parser->subparse(array($this, 'decideWithEnd'), true);

        $stream->expect(Twig_Token::BLOCK_END_TYPE);
        return new Twig_Node_With($body, $setter, $lineno, $this->getTag());
    }

    public function decideWithEnd(Twig_Token $token)
    {
        return $token->test('endwith');
    }

    /**
     * Gets the tag name associated with this token parser.
     *
     * @return string The tag name
     */
    public function getTag()
    {
        return 'with';
    }
}
