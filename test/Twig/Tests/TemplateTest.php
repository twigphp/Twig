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
    /**
     * @dataProvider getGetAttributeTests
     */
    public function testGetAttribute($defined, $value, $object, $item, $arguments, $type)
    {
        $template = new Twig_TemplateTest(new Twig_Environment());

        $this->assertEquals($value, $template->getAttribute($object, $item, $arguments, $type));
    }

    /**
     * @dataProvider getGetAttributeTests
     */
    public function testGetAttributeStrict($defined, $value, $object, $item, $arguments, $type)
    {
        $template = new Twig_TemplateTest(
            new Twig_Environment(null, array('strict_variables' => true))
        );

        if ($defined) {
            $this->assertEquals($value, $template->getAttribute($object, $item, $arguments, $type));
        } else {
            try {
                $this->assertEquals($value, $template->getAttribute($object, $item, $arguments, $type));

                throw new Exception('Expected Twig_Error_Runtime exception.');
            } catch (Twig_Error_Runtime $e) { }
        }
    }

    /**
     * @dataProvider getGetAttributeTests
     */
    public function testGetAttributeDefined($defined, $value, $object, $item, $arguments, $type)
    {
        $template = new Twig_TemplateTest(new Twig_Environment());

        $this->assertEquals($defined, $template->getAttribute($object, $item, $arguments, $type, true));
    }

    /**
     * @dataProvider getGetAttributeTests
     */
    public function testGetAttributeDefinedStrict($defined, $value, $object, $item, $arguments, $type)
    {
        $template = new Twig_TemplateTest(
            new Twig_Environment(null, array('strict_variables' => true))
        );

        $this->assertEquals($defined, $template->getAttribute($object, $item, $arguments, $type, true));
    }

    public function getGetAttributeTests()
    {
        $array = array(
            'defined' => 'defined',
            'zero'    => 0,
            'null'    => null,
        );

        $objectArray         = new Twig_TemplateArrayAccessObject;
        $stdObject           = (object) $array;
        $magicPropertyObject = new Twig_TemplateMagicPropertyObject;
        $methodObject        = new Twig_TemplateMethodObject;
        $magicMethodObject   = new Twig_TemplateMagicMethodObject;

        $anyType    = Twig_TemplateInterface::ANY_CALL;
        $methodType = Twig_TemplateInterface::METHOD_CALL;
        $arrayType  = Twig_TemplateInterface::ARRAY_CALL;

        $basicTests = array(
            // array(defined, value, property to fetch)
            array(true,  'defined', 'defined'),
            array(false, null,      'undefined'),
            array(true,  0,         'zero'),
            array(true,  null,      'null'),
        );
        $testObjects = array(
            // array(object, type of fetch)
            array($array,               $arrayType),
            array($objectArray,         $arrayType),
            array($stdObject,           $anyType),
            array($magicPropertyObject, $anyType),
            array($methodObject,        $methodType),
        );

        $tests = array();
        foreach ($testObjects as $testObject) {
            foreach ($basicTests as $test) {
                $tests[] = array($test[0], $test[1], $testObject[0], $test[2], array(), $testObject[1]);
            }
        }

        // additional method tests
        $tests = array_merge($tests, array(
            array(true, 'defined', $methodObject, 'defined',    array(), $methodType),
            array(true, 'defined', $methodObject, 'DEFINED',    array(), $methodType),
            array(true, 'defined', $methodObject, 'getDefined', array(), $methodType),
            array(true, 'defined', $methodObject, 'GETDEFINED', array(), $methodType),
            array(true, 'static',  $methodObject, 'static',     array(), $methodType),
            array(true, 'static',  $methodObject, 'getStatic',  array(), $methodType),

            array(true, '__call_undefined', $magicMethodObject, 'undefined', array(), $methodType),
            array(true, '__call_UNDEFINED', $magicMethodObject, 'UNDEFINED', array(), $methodType),
        ));

        // add the same tests for the any type
        foreach ($tests as $test) {
            if ($anyType !== $test[5]) {
                $test[5] = $anyType;
                $tests[] = $test;
            }
        }

        return $tests;
    }
}

class Twig_TemplateTest extends Twig_Template
{
    protected function doDisplay(array $context, array $blocks = array())
    {
    }

    public function getAttribute($object, $item, array $arguments = array(), $type = Twig_TemplateInterface::ANY_CALL, $isDefinedTest = false)
    {
        return parent::getAttribute($object, $item, $arguments, $type, $isDefinedTest);
    }
}

class Twig_TemplateArrayAccessObject implements ArrayAccess
{
    public $attributes = array(
        'defined' => 'defined',
        'zero'    => 0,
        'null'    => null,
    );

    public function offsetExists($name)
    {
        return array_key_exists($name, $this->attributes);
    }

    public function offsetGet($name)
    {
        return array_key_exists($name, $this->attributes) ? $this->attributes[$name] : null;
    }

    public function offsetSet($name, $value)
    {
    }

    public function offsetUnset($name)
    {
    }
}

class Twig_TemplateMagicPropertyObject
{
    public $attributes = array(
        'defined' => 'defined',
        'zero'    => 0,
        'null'    => null,
    );

    public function __isset($name)
    {
        return array_key_exists($name, $this->attributes);
    }

    public function __get($name)
    {
        return array_key_exists($name, $this->attributes) ? $this->attributes[$name] : null;
    }
}

class Twig_TemplateMethodObject
{
    public function getDefined()
    {
        return 'defined';
    }

    public function getZero()
    {
        return 0;
    }

    public function getNull()
    {
        return null;
    }

    static public function getStatic()
    {
        return 'static';
    }
}

class Twig_TemplateMagicMethodObject
{
    public function __call($method, $arguments) {
        return '__call_'.$method;
    }
}
