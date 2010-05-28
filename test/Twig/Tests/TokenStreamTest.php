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
        $stream = new Twig_TokenStream(self::$tokens, '', false);
        $repr = array();
        while (!$stream->isEOF()) {
            $token = $stream->next();

            $repr[] = $token->getValue();
        }
        $this->assertEquals('1, 2, 3, 4, 5, 6, 7', implode(', ', $repr), '->next() returns the next token in the stream');
    }

    public function testLook()
    {
        $stream = new Twig_TokenStream(self::$tokens, '', false);
        $this->assertEquals(2, $stream->look()->getValue(), '->look() returns the next token');
        $repr = array();
        while (!$stream->isEOF()) {
            $token = $stream->next();

            $repr[] = $token->getValue();
        }
        $this->assertEquals('1, 2, 3, 4, 5, 6, 7', implode(', ', $repr), '->look() pushes the token to the stack');

        $stream = new Twig_TokenStream(self::$tokens, '', false);
        $this->assertEquals(2, $stream->look()->getValue(), '->look() returns the next token');
        $this->assertEquals(3, $stream->look()->getValue(), '->look() can be called several times to look more than one upcoming token');
        $this->assertEquals(4, $stream->look()->getValue(), '->look() can be called several times to look more than one upcoming token');
        $this->assertEquals(5, $stream->look()->getValue(), '->look() can be called several times to look more than one upcoming token');
        $repr = array();
        while (!$stream->isEOF()) {
            $token = $stream->next();

            $repr[] = $token->getValue();
        }
        $this->assertEquals('1, 2, 3, 4, 5, 6, 7', implode(', ', $repr), '->look() pushes the token to the stack');
    }

    public function testRewind()
    {
        $stream = new Twig_TokenStream(self::$tokens, '', false);
        $this->assertEquals(2, $stream->look()->getValue(), '->look() returns the next token');
        $this->assertEquals(3, $stream->look()->getValue(), '->look() can be called several times to look more than one upcoming token');
        $this->assertEquals(4, $stream->look()->getValue(), '->look() can be called several times to look more than one upcoming token');
        $this->assertEquals(5, $stream->look()->getValue(), '->look() can be called several times to look more than one upcoming token');
        $stream->rewind();
        $repr = array();
        while (!$stream->isEOF()) {
            $token = $stream->next(false);

            $repr[] = $token->getValue();
        }
        $this->assertEquals('1, 2, 3, 4, 5, 6, 7', implode(', ', $repr), '->rewind() pushes all pushed tokens to the token array');
    }
}
