<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Twig_Tests_LexerTest extends PHPUnit_Framework_TestCase
{
    public function testBracketsNesting()
    {
        $template = '{{ {"a":{"b":"c"}} }}';

        $this->assertEquals(2, $this->countToken($template, Twig_Token::PUNCTUATION_TYPE, '{'));
        $this->assertEquals(2, $this->countToken($template, Twig_Token::PUNCTUATION_TYPE, '}'));
    }

    protected function countToken($template, $type, $value = null)
    {
        $lexer = new Twig_Lexer(new Twig_Environment());
        $stream = $lexer->tokenize($template);

        $count = 0;
        $tokens = array();
        while (!$stream->isEOF()) {
            $token = $stream->next();
            if ($type === $token->getType()) {
                if (null === $value || $value === $token->getValue()) {
                    ++$count;
                }
            }
        }

        return $count;
    }

    public function testLineDirective()
    {
        $template = "foo\n"
            . "bar\n"
            . "{% line 10 %}\n"
            . "{{\n"
            . "baz\n"
            . "}}\n";

        $lexer = new Twig_Lexer(new Twig_Environment());
        $stream = $lexer->tokenize($template);

        // foo\nbar\n
        $this->assertSame(1, $stream->expect(Twig_Token::TEXT_TYPE)->getLine());
        // \n (after {% line %})
        $this->assertSame(10, $stream->expect(Twig_Token::TEXT_TYPE)->getLine());
        // {{
        $this->assertSame(11, $stream->expect(Twig_Token::VAR_START_TYPE)->getLine());
        // baz
        $this->assertSame(12, $stream->expect(Twig_Token::NAME_TYPE)->getLine());
    }

    public function testLineDirectiveInline()
    {
        $template = "foo\n"
            . "bar{% line 10 %}{{\n"
            . "baz\n"
            . "}}\n";

        $lexer = new Twig_Lexer(new Twig_Environment());
        $stream = $lexer->tokenize($template);

        // foo\nbar
        $this->assertSame(1, $stream->expect(Twig_Token::TEXT_TYPE)->getLine());
        // {{
        $this->assertSame(10, $stream->expect(Twig_Token::VAR_START_TYPE)->getLine());
        // baz
        $this->assertSame(11, $stream->expect(Twig_Token::NAME_TYPE)->getLine());
    }
}
