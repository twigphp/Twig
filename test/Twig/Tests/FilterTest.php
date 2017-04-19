<?php


class Twig_Tests_FilterTest extends PHPUnit_Framework_TestCase
{
    protected $env;

    protected function setUp()
    {
        parent::setUp();
        $this->env = new Twig_Environment(new Twig_Loader_Array(array()));
    }

    public function sliceProvider()
    {
        return array(
            array(array('a', 'b'), 1, 1, false, array('b')),
            array(array('a' => 'b', 'b' => 'c'), 1, 1, false, array('b' => 'c')),

        );
    }

    public function keysProvider()
    {
        return array(
            array(array('a', 'b'), array(0, 1)),
            array(array('a' => 'b', 'b' => 'c'), array('a', 'b')),

        );
    }

    /**
     * @dataProvider sliceProvider
     */
    public function testSlice($item, $start, $length, $preserveKeys, $expected)
    {
        $this->assertEquals($expected, twig_slice($this->env, $item, $start, $length, $preserveKeys));
    }

    /**
     * @param $array
     * @param $expected
     * @dataProvider keysProvider
     */
    public function testKeys($array, $expected)
    {
        $this->assertEquals($expected, twig_get_array_keys_filter($array));
    }
}
