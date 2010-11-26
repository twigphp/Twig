<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Twig_Tests_SimpleTokenParserTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getTests
     */
    public function testParseGrammar($str, $grammar)
    {
        $this->assertEquals($grammar, Twig_SimpleTokenParser::parseGrammar($str), '::parseGrammar() parses a grammar');
    }

    public function testParseGrammarExceptions()
    {
        try {
            Twig_SimpleTokenParser::parseGrammar('<foo:foo>');
            $this->fail();
        } catch (Exception $e) {
            $this->assertEquals('Twig_Error_Runtime', get_class($e));
        }

        try {
            Twig_SimpleTokenParser::parseGrammar('<foo:foo');
            $this->fail();
        } catch (Exception $e) {
            $this->assertEquals('Twig_Error_Runtime', get_class($e));
        }

        try {
            Twig_SimpleTokenParser::parseGrammar('<foo:foo> (with');
            $this->fail();
        } catch (Exception $e) {
            $this->assertEquals('Twig_Error_Runtime', get_class($e));
        }
    }

    public function getTests()
    {
        return array(
            array('', new Twig_Grammar_Tag()),
            array('const', new Twig_Grammar_Tag(
                new Twig_Grammar_Constant('const')
            )),
            array('   const   ', new Twig_Grammar_Tag(
                new Twig_Grammar_Constant('const')
            )),
            array('<expr>', new Twig_Grammar_Tag(
                new Twig_Grammar_Expression('expr')
            )),
            array('<expr:expression>', new Twig_Grammar_Tag(
                new Twig_Grammar_Expression('expr')
            )),
            array('   <expr:expression>   ', new Twig_Grammar_Tag(
                new Twig_Grammar_Expression('expr')
            )),
            array('<nb:number>', new Twig_Grammar_Tag(
                new Twig_Grammar_Number('nb')
            )),
            array('<bool:boolean>', new Twig_Grammar_Tag(
                new Twig_Grammar_Boolean('bool')
            )),
            array('<content:body>', new Twig_Grammar_Tag(
                new Twig_Grammar_Body('content')
            )),
            array('<expr:expression> [with <arguments:array>]', new Twig_Grammar_Tag(
                new Twig_Grammar_Expression('expr'),
                new Twig_Grammar_Optional(
                    new Twig_Grammar_Constant('with'),
                    new Twig_Grammar_Array('arguments')
                )
            )),
            array('  <expr:expression>   [  with   <arguments:array> ]  ', new Twig_Grammar_Tag(
                new Twig_Grammar_Expression('expr'),
                new Twig_Grammar_Optional(
                    new Twig_Grammar_Constant('with'),
                    new Twig_Grammar_Array('arguments')
                )
            )),
            array('<expr:expression> [with <arguments:array> [or <optional:expression>]]', new Twig_Grammar_Tag(
                new Twig_Grammar_Expression('expr'),
                new Twig_Grammar_Optional(
                    new Twig_Grammar_Constant('with'),
                    new Twig_Grammar_Array('arguments'),
                    new Twig_Grammar_Optional(
                        new Twig_Grammar_Constant('or'),
                        new Twig_Grammar_Expression('optional')
                    )
                )
            )),
            array('<expr:expression> [with <arguments:array> [, <optional:expression>]]', new Twig_Grammar_Tag(
                new Twig_Grammar_Expression('expr'),
                new Twig_Grammar_Optional(
                    new Twig_Grammar_Constant('with'),
                    new Twig_Grammar_Array('arguments'),
                    new Twig_Grammar_Optional(
                        new Twig_Grammar_Constant(',', Twig_Token::PUNCTUATION_TYPE),
                        new Twig_Grammar_Expression('optional')
                    )
                )
            )),
        );
    }
}
