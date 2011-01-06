<?php

/*
 * This file is part of Twig.
 *
 * (c) 2010 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Interface implemented by token parsers.
 *
 * @package twig
 * @author  Fabien Potencier <fabien.potencier@symfony-project.com>
 */
interface Twig_TokenParserInterface
{
    /**
     * Sets the parser associated with this token parser
     *
     * @param $parser A Twig_Parser instance
     */
    function setParser(Twig_Parser $parser);

    /**
     * Parses a token and returns a node.
     *
     * @param Twig_Token $token A Twig_Token instance
     *
     * @return Twig_NodeInterface A Twig_NodeInterface instance
     */
    function parse(Twig_Token $token);

    /**
     * Gets the tag name associated with this token parser.
     *
     * @param string The tag name
     */
    function getTag();
}
