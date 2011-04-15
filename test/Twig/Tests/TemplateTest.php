<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Twig_Tests_TemplateTest extends PHPUnit_Framework_TestCase
{
    public function getUnknownPropertyOnArrayTests()
    {
        $tests = array(
            array(array('foo' => 'foo', 'bar' => 'value')),
            array(new Twig_TemplateObjectArrayAccess()),
        );

        return $tests;
    }

    /**
     * @dataProvider getUnknownPropertyOnArrayTests
     * @expectedException Twig_Error_Runtime
     */
    public function testUnknownPropertyOnArray($array)
    {
        list($template, $m) = $this->getTemplate(true);

        $m->invoke($template, $array, 'unknown', array(), Twig_TemplateInterface::ARRAY_CALL);
    }

    /**
     * @dataProvider getGetAttributeTests
     */
    public function testGetAttribute($expected, $object, $item, $arguments, $type)
    {
        list($template, $m) = $this->getTemplate(false);

        $this->assertEquals($expected, $m->invoke($template, $object, $item, $arguments, $type));
    }

    protected function getTemplate($strict)
    {
        $env = new Twig_Environment(null, array('strict_variables' => $strict));
        $template = $this->getMockForAbstractClass('Twig_Template', array($env));
        $r = new ReflectionObject($template);
        $m = $r->getMethod('getAttribute');
        $m->setAccessible(true);

        return array($template, $m);
    }

    public function getGetAttributeTests()
    {
        $array = array('foo' => 'foo');
        $object = new Twig_TemplateObject();
        $objectArray = new Twig_TemplateObjectArrayAccess();
        $objectMagic = new Twig_TemplateObjectMagic();

        $anyType = Twig_TemplateInterface::ANY_CALL;
        $methodType = Twig_TemplateInterface::METHOD_CALL;
        $arrayType = Twig_TemplateInterface::ARRAY_CALL;

        $tests = array(
            // ARRAY
                array('foo', $array, 'foo', array(), $arrayType),
                array(null, $array, 'foobar', array(), $arrayType),
                array('foo', $objectArray, 'foo', array(), $arrayType),
                array(null, $objectArray, 'foobar', array(), $arrayType),

            // METHOD
                array('bar', $object, 'bar', array(), $methodType),
                array('bar', $object, 'getBar', array(), $methodType),
                array('bar', $object, 'getbar', array(), $methodType),
                array('foobar', $object, 'foobar', array(), $methodType),
                array('babar', $object, 'babar', array(), $methodType),
                array('babarStatic', $object, 'babarStatic', array(), $methodType),
                array('__call_baz', $objectMagic, 'baz', array(), $methodType),
                array('__call_Baz', $objectMagic, 'Baz', array(), $methodType),

            // ANY
                array('foo', $object, 'foo', array(), $anyType),
                array('foo', $objectMagic, 'foo', array(), $anyType),
                array('Foo', $objectMagic, 'Foo', array(), $anyType),
                array('babar', $object, 'babar', array(), $anyType),
                array(null, $object, 'null', array(), $anyType),
        );

        // add the same tests for the any type
        foreach ($tests as $test) {
            if ($anyType !== $test[4]) {
                $test[4] = $anyType;
                $tests[] = $test;
            }
        }

        return $tests;
    }
}

class Twig_TemplateObject
{
    public $foo = 'foo';
    public $null = null;
    protected $babar = 'babar...';
    static protected $babarStatic = 'babarStatic...';

    static public function getBabarStatic()
    {
        return 'babarStatic';
    }

    public function getBabar()
    {
        return 'babar';
    }

    public function getNull()
    {
        return 'null...';
    }

    public function getBar()
    {
        return 'bar';
    }

    public function fooBar()
    {
        return 'foobar';
    }
}

class Twig_TemplateObjectArrayAccess implements ArrayAccess
{
    public $attributes = array('foo' => 'foo');

    public function offsetExists($name)
    {
        return isset($this->attributes[$name]);
    }

    public function offsetGet($name)
    {
        return isset($this->attributes[$name]) ? $this->attributes[$name] : null;
    }

    public function offsetSet($name, $value)
    {
    }

    public function offsetUnset($name)
    {
    }
}

class Twig_TemplateObjectMagic
{
    public $attributes = array('foo' => 'foo', 'Foo' => 'Foo');

    public function __isset($name)
    {
        return isset($this->attributes[$name]);
    }

    public function __get($name)
    {
        return isset($this->attributes[$name]) ? $this->attributes[$name] : null;
    }

    public function __call($method, $arguments)
    {
        return '__call_'.$method;
    }
}
