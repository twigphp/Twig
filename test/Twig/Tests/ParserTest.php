<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Twig_Tests_ParserTest extends PHPUnit_Framework_TestCase
{
    /**
     * @expectedException Twig_Error_Syntax
     */
    public function testSetMacroThrowsExceptionOnReservedMethods()
    {
        $parser = $this->getParser();
        $parser->setMacro('display', $this->getMock('Twig_Node_Macro', array(), array(), '', null));
    }

    /**
     * @expectedException        Twig_Error_Syntax
     * @expectedExceptionMessage Unknown tag name "foo". Did you mean "for" at line 1
     */
    public function testUnknownTag()
    {
        $stream = new Twig_TokenStream(array(
            new Twig_Token(Twig_Token::BLOCK_START_TYPE, '', 1),
            new Twig_Token(Twig_Token::NAME_TYPE, 'foo', 1),
            new Twig_Token(Twig_Token::BLOCK_END_TYPE, '', 1),
            new Twig_Token(Twig_Token::EOF_TYPE, '', 1),
        ));
        $parser = new Twig_Parser(new Twig_Environment());
        $parser->parse($stream);
    }

    /**
     * @dataProvider getFilterBodyNodesData
     */
    public function testFilterBodyNodes($input, $expected)
    {
        $parser = $this->getParser();

        $this->assertEquals($expected, $parser->filterBodyNodes($input));
    }

    public function getFilterBodyNodesData()
    {
        return array(
            array(
                new Twig_Node(array(new Twig_Node_Text('   ', 1))),
                new Twig_Node(array()),
            ),
            array(
                $input = new Twig_Node(array(new Twig_Node_Set(false, new Twig_Node(), new Twig_Node(), 1))),
                $input,
            ),
            array(
                $input = new Twig_Node(array(new Twig_Node_Set(true, new Twig_Node(), new Twig_Node(array(new Twig_Node(array(new Twig_Node_Text('foo', 1))))), 1))),
                $input,
            ),
        );
    }

    /**
     * @dataProvider getFilterBodyNodesDataThrowsException
     * @expectedException Twig_Error_Syntax
     */
    public function testFilterBodyNodesThrowsException($input)
    {
        $parser = $this->getParser();

        $parser->filterBodyNodes($input);
    }

    public function getFilterBodyNodesDataThrowsException()
    {
        return array(
            array(new Twig_Node_Text('foo', 1)),
            array(new Twig_Node(array(new Twig_Node(array(new Twig_Node_Text('foo', 1)))))),
        );
    }

    /**
     * @expectedException Twig_Error_Syntax
     * @expectedExceptionMessage A template that extends another one cannot have a body but a byte order mark (BOM) has been detected; it must be removed at line 1.
     */
    public function testFilterBodyNodesWithBOM()
    {
        $parser = $this->getParser();
        $parser->filterBodyNodes(new Twig_Node_Text(chr(0xEF).chr(0xBB).chr(0xBF), 1));
    }

    public function testParseIsReentrant()
    {
        $twig = new Twig_Environment(null, array(
            'autoescape' => false,
            'optimizations' => 0,
        ));
        $twig->addTokenParser(new TestTokenParser());

        $parser = new Twig_Parser($twig);

        $parser->parse(new Twig_TokenStream(array(
            new Twig_Token(Twig_Token::BLOCK_START_TYPE, '', 1),
            new Twig_Token(Twig_Token::NAME_TYPE, 'test', 1),
            new Twig_Token(Twig_Token::BLOCK_END_TYPE, '', 1),
            new Twig_Token(Twig_Token::VAR_START_TYPE, '', 1),
            new Twig_Token(Twig_Token::NAME_TYPE, 'foo', 1),
            new Twig_Token(Twig_Token::VAR_END_TYPE, '', 1),
            new Twig_Token(Twig_Token::EOF_TYPE, '', 1),
        )));

        $this->assertEquals(null, $parser->getParent());
    }

    // The getVarName() must not depend on the template loaders,
    // If this test does not throw any exception, that's good.
    // see https://github.com/symfony/symfony/issues/4218
    public function testGetVarName()
    {
        $twig = new Twig_Environment(null, array(
            'autoescape' => false,
            'optimizations' => 0,
        ));

        $twig->parse($twig->tokenize(<<<EOF
{% from _self import foo %}

{% macro foo() %}
    {{ foo }}
{% endmacro %}
EOF
        ));
    }

    protected function getParser()
    {
        $parser = new TestParser(new Twig_Environment());
        $parser->setParent(new Twig_Node());
        $parser->stream = $this->getMockBuilder('Twig_TokenStream')->disableOriginalConstructor()->getMock();

        return $parser;
    }
}

class TestParser extends Twig_Parser
{
    public $stream;

    public function filterBodyNodes(Twig_NodeInterface $node)
    {
        return parent::filterBodyNodes($node);
    }
}

class TestTokenParser extends Twig_TokenParser
{
    public function parse(Twig_Token $token)
    {
        // simulate the parsing of another template right in the middle of the parsing of the current template
        $this->parser->parse(new Twig_TokenStream(array(
            new Twig_Token(Twig_Token::BLOCK_START_TYPE, '', 1),
            new Twig_Token(Twig_Token::NAME_TYPE, 'extends', 1),
            new Twig_Token(Twig_Token::STRING_TYPE, 'base', 1),
            new Twig_Token(Twig_Token::BLOCK_END_TYPE, '', 1),
            new Twig_Token(Twig_Token::EOF_TYPE, '', 1),
        )));

        $this->parser->getStream()->expect(Twig_Token::BLOCK_END_TYPE);

        return new Twig_Node(array());
    }

    public function getTag()
    {
        return 'test';
    }
}
