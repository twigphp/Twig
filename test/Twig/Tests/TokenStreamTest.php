<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Twig_Tests_TokenStreamTest extends PHPUnit_Framework_TestCase
{
    static protected $tokens;

    public function setUp()
    {
        self::$tokens = array(
            new Twig_Token(Twig_Token::TEXT_TYPE, 1, 0),
            new Twig_Token(Twig_Token::TEXT_TYPE, 2, 0),
            new Twig_Token(Twig_Token::TEXT_TYPE, 3, 0),
            new Twig_Token(Twig_Token::TEXT_TYPE, 4, 0),
            new Twig_Token(Twig_Token::TEXT_TYPE, 5, 0),
            new Twig_Token(Twig_Token::TEXT_TYPE, 6, 0),
            new Twig_Token(Twig_Token::TEXT_TYPE, 7, 0),
            new Twig_Token(Twig_Token::EOF_TYPE, 0, 0),
        );
    }

    public function testNext()
    {
        $stream = new Twig_TokenStream(self::$tokens);
        $repr = array();
        while (!$stream->isEOF()) {
            $token = $stream->next();

            $repr[] = $token->getValue();
        }
        $this->assertEquals('1, 2, 3, 4, 5, 6, 7', implode(', ', $repr), '->next() advances the pointer and returns the current token');
    }
}
