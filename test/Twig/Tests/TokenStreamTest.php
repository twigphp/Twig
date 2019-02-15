<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Twig_Tests_TokenStreamTest extends \PHPUnit\Framework\TestCase
{
    protected static $tokens;

    protected function setUp()
    {
        self::$tokens = [
            new \Twig\Token(\Twig\Token::TEXT_TYPE, 1, 1),
            new \Twig\Token(\Twig\Token::TEXT_TYPE, 2, 1),
            new \Twig\Token(\Twig\Token::TEXT_TYPE, 3, 1),
            new \Twig\Token(\Twig\Token::TEXT_TYPE, 4, 1),
            new \Twig\Token(\Twig\Token::TEXT_TYPE, 5, 1),
            new \Twig\Token(\Twig\Token::TEXT_TYPE, 6, 1),
            new \Twig\Token(\Twig\Token::TEXT_TYPE, 7, 1),
            new \Twig\Token(\Twig\Token::EOF_TYPE, 0, 1),
        ];
    }

    public function testNext()
    {
        $stream = new \Twig\TokenStream(self::$tokens);
        $repr = [];
        while (!$stream->isEOF()) {
            $token = $stream->next();

            $repr[] = $token->getValue();
        }
        $this->assertEquals('1, 2, 3, 4, 5, 6, 7', implode(', ', $repr), '->next() advances the pointer and returns the current token');
    }

    /**
     * @expectedException        \Twig\Error\SyntaxError
     * @expectedExceptionMessage Unexpected end of template
     */
    public function testEndOfTemplateNext()
    {
        $stream = new \Twig\TokenStream([
            new \Twig\Token(\Twig\Token::BLOCK_START_TYPE, 1, 1),
        ]);
        while (!$stream->isEOF()) {
            $stream->next();
        }
    }

    /**
     * @expectedException        \Twig\Error\SyntaxError
     * @expectedExceptionMessage Unexpected end of template
     */
    public function testEndOfTemplateLook()
    {
        $stream = new \Twig\TokenStream([
            new \Twig\Token(\Twig\Token::BLOCK_START_TYPE, 1, 1),
        ]);
        while (!$stream->isEOF()) {
            $stream->look();
            $stream->next();
        }
    }
}
