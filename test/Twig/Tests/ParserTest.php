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
        $parser = new Twig_Parser(new Twig_Environment());
        $parser->setMacro('display', $this->getMock('Twig_Node_Macro', array(), array(), '', null));
    }

    /**
     * @expectedException        Twig_Error_Syntax
     * @expectedExceptionMessage Unknown tag name "foo". Did you mean "for" at line 0
     */
    public function testUnkownTag()
    {
        $stream = new Twig_TokenStream(array(
            new Twig_Token(Twig_Token::BLOCK_START_TYPE, '', 0),
            new Twig_Token(Twig_Token::NAME_TYPE, 'foo', 0),
            new Twig_Token(Twig_Token::BLOCK_END_TYPE, '', 0),
            new Twig_Token(Twig_Token::EOF_TYPE, '', 0),
        ));
        $parser = new Twig_Parser(new Twig_Environment());
        $parser->parse($stream);
    }

    /**
     * @dataProvider getFilterBodyNodesData
     */
    public function testFilterBodyNodes($input, $expected)
    {
        $parser = $this->getParserForFilterBodyNodes();

        $this->assertEquals($expected, $parser->filterBodyNodes($input));
    }

    public function getFilterBodyNodesData()
    {
        return array(
            array(
                new Twig_Node(array(new Twig_Node_Text('   ', 0))),
                new Twig_Node(array()),
            ),
            array(
                $input = new Twig_Node(array(new Twig_Node_Set(false, new Twig_Node(), new Twig_Node(), 0))),
                $input,
            ),
            array(
                $input = new Twig_Node(array(new Twig_Node_Set(true, new Twig_Node(), new Twig_Node(array(new Twig_Node(array(new Twig_Node_Text('foo', 0))))), 0))),
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
        $parser = $this->getParserForFilterBodyNodes();

        $parser->filterBodyNodes($input);
    }

    public function getFilterBodyNodesDataThrowsException()
    {
        return array(
            array(new Twig_Node_Text('foo', 0)),
            array(new Twig_Node(array(new Twig_Node(array(new Twig_Node_Text('foo', 0)))))),
        );
    }

    /**
     * @expectedException Twig_Error_Syntax
     * @expectedExceptionMessage A template that extends another one cannot have a body but a byte order mark (BOM) has been detected; it must be removed at line 0.
     */
    public function testFilterBodyNodesWithBOM()
    {
        $parser = $this->getParserForFilterBodyNodes();
        $parser->filterBodyNodes(new Twig_Node_Text(chr(0xEF).chr(0xBB).chr(0xBF), 0));
    }

    protected function getParserForFilterBodyNodes()
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
