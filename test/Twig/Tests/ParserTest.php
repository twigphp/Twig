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
