<?php

namespace Twig\Tests;

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Error\SyntaxError;
use Twig\Lexer;
use Twig\Loader\LoaderInterface;
use Twig\Source;
use Twig\Token;

class LexerNewTest extends TestCase
{
    /**
     * @dataProvider provideSyntaxes
     */
    public function testNameLabelForTag(string $code, array $expected)
    {
        $lexer = new Lexer(new Environment($this->createMock(LoaderInterface::class)));
        $stream = $lexer->tokenize(new Source($code, 'index'));

        foreach ($expected as $expectedToken) {
            $this->assertNotEmpty($stream->expect($expectedToken[0], $expectedToken[1]));
        }

        $this->assertTrue($stream->isEOF());
    }

    public function provideSyntaxes()
    {
        yield 'Empty block' => [
            '{## ##}',
            []
        ];
        yield 'Empty block after text' => [
            'L {## ##}',
            [
                [Token::TEXT_TYPE, 'L ']
            ]
        ];
        yield 'Empty block before text' => [
            '{## ##} R',
            [
                [Token::TEXT_TYPE, ' R']
            ]
        ];
        yield 'Comment block around text' => [
            '{## M ##}',
            [],
        ];
    }
}
