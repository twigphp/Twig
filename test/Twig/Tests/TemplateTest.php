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
     * @expectedException        Twig_Error_Runtime
     * @expectedExceptionMessage Impossible to access a key ("a") on a "string" variable
     */
    public function testAttributeOnAString()
    {
        $template = new Twig_TemplateTest(
            new Twig_Environment(null, array('strict_variables' => true)),
            false
        );

        $template->getAttribute('string', 'a', array(), Twig_TemplateInterface::ARRAY_CALL, false);
    }

    /**
     * @dataProvider getGetAttributeTests
     */
    public function testGetAttribute($defined, $value, $object, $item, $arguments, $type, $useExt = false)
    {
        $template = new Twig_TemplateTest(
            new Twig_Environment(),
            $useExt
        );

        $this->assertEquals($value, $template->getAttribute($object, $item, $arguments, $type));
    }

    /**
     * @dataProvider getGetAttributeTests
     */
    public function testGetAttributeStrict($defined, $value, $object, $item, $arguments, $type, $useExt = false, $exceptionMessage = null)
    {
        $template = new Twig_TemplateTest(
            new Twig_Environment(null, array('strict_variables' => true)),
            $useExt
        );

        if ($defined) {
            $this->assertEquals($value, $template->getAttribute($object, $item, $arguments, $type));
        } else {
            try {
                $this->assertEquals($value, $template->getAttribute($object, $item, $arguments, $type));

                throw new Exception('Expected Twig_Error_Runtime exception.');
            } catch (Twig_Error_Runtime $e) {
                if (null !== $exceptionMessage) {
                    $this->assertSame($exceptionMessage, $e->getMessage());
                }
            }
        }
    }

    /**
     * @dataProvider getGetAttributeTests
     */
    public function testGetAttributeDefined($defined, $value, $object, $item, $arguments, $type, $useExt = false)
    {
        $template = new Twig_TemplateTest(
            new Twig_Environment(),
            $useExt
        );

        $this->assertEquals($defined, $template->getAttribute($object, $item, $arguments, $type, true));
    }

    /**
     * @dataProvider getGetAttributeTests
     */
    public function testGetAttributeDefinedStrict($defined, $value, $object, $item, $arguments, $type, $useExt = false)
    {
        $template = new Twig_TemplateTest(
            new Twig_Environment(null, array('strict_variables' => true)),
            $useExt
        );

        $this->assertEquals($defined, $template->getAttribute($object, $item, $arguments, $type, true));
    }

    public function getGetAttributeTests()
    {
        $array = array(
            'defined' => 'defined',
            'zero'    => 0,
            'null'    => null,
            '1'       => 1,
        );

        $objectArray         = new Twig_TemplateArrayAccessObject();
        $stdObject           = (object) $array;
        $magicPropertyObject = new Twig_TemplateMagicPropertyObject();
        $propertyObject      = new Twig_TemplatePropertyObject();
        $propertyObject1     = new Twig_TemplatePropertyObjectAndIterator();
        $methodObject        = new Twig_TemplateMethodObject();
        $magicMethodObject   = new Twig_TemplateMagicMethodObject();

        $anyType    = Twig_TemplateInterface::ANY_CALL;
        $methodType = Twig_TemplateInterface::METHOD_CALL;
        $arrayType  = Twig_TemplateInterface::ARRAY_CALL;

        $basicTests = array(
            // array(defined, value, property to fetch)
            array(true,  'defined', 'defined'),
            array(false, null,      'undefined'),
            array(false, null,      'protected'),
            array(true,  0,         'zero'),
            array(true,  1,         1),
            array(true,  1,         1.0),
            array(true,  null,      'null'),
        );
        $testObjects = array(
            // array(object, type of fetch)
            array($array,               $arrayType),
            array($objectArray,         $arrayType),
            array($stdObject,           $anyType),
            array($magicPropertyObject, $anyType),
            array($methodObject,        $methodType),
            array($methodObject,        $anyType),
            array($propertyObject,      $anyType),
            array($propertyObject1,     $anyType),
        );

        $tests = array();
        foreach ($testObjects as $testObject) {
            foreach ($basicTests as $test) {
                // properties cannot be numbers
                if (($testObject[0] instanceof stdClass || $testObject[0] instanceof Twig_TemplatePropertyObject) && is_numeric($test[2])) {
                    continue;
                }

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

        $methodAndPropObject = new Twig_TemplateMethodAndPropObject;

        // additional method tests
        $tests = array_merge($tests, array(
            array(true, 'a', $methodAndPropObject, 'a', array(), $anyType),
            array(true, 'a', $methodAndPropObject, 'a', array(), $methodType),
            array(false, null, $methodAndPropObject, 'a', array(), $arrayType),

            array(true, 'b_prop', $methodAndPropObject, 'b', array(), $anyType),
            array(true, 'b', $methodAndPropObject, 'B', array(), $anyType),
            array(true, 'b', $methodAndPropObject, 'b', array(), $methodType),
            array(true, 'b', $methodAndPropObject, 'B', array(), $methodType),
            array(false, null, $methodAndPropObject, 'b', array(), $arrayType),

            array(false, null, $methodAndPropObject, 'c', array(), $anyType),
            array(false, null, $methodAndPropObject, 'c', array(), $methodType),
            array(false, null, $methodAndPropObject, 'c', array(), $arrayType),

        ));

        // tests when input is not an array or object
        $tests = array_merge($tests, array(
            array(false, null, 42, 'a', array(), $anyType, false, 'Item "a" for "42" does not exist'),
            array(false, null, "string", 'a', array(), $anyType, false, 'Item "a" for "string" does not exist'),
            array(false, null, array(), 'a', array(), $anyType, false, 'Item "a" for "Array" does not exist'),
        ));

        // add twig_template_get_attributes tests

        if (function_exists('twig_template_get_attributes')) {
            foreach(array_slice($tests, 0) as $test) {
                $test = array_pad($test, 7, null);
                $test[6] = true;
                $tests[] = $test;
            }
        }

        return $tests;
    }

    public function useExtGetAttribute()
    {
        return false;
    }
}

class Twig_TemplateTest extends Twig_Template
{
    protected $useExtGetAttribute = false;

    public function __construct(Twig_Environment $env, $useExtGetAttribute = false)
    {
        parent::__construct($env);
        $this->useExtGetAttribute = $useExtGetAttribute;
        Twig_Template::clearCache();
    }

    public function getTemplateName()
    {
    }

    public function getDebugInfo()
    {
        return array();
    }

    protected function doGetParent(array $context)
    {
    }

    protected function doDisplay(array $context, array $blocks = array())
    {
    }

    public function getAttribute($object, $item, array $arguments = array(), $type = Twig_TemplateInterface::ANY_CALL, $isDefinedTest = false, $ignoreStrictCheck = false)
    {
        if ($this->useExtGetAttribute) {
            return twig_template_get_attributes($this, $object, $item, $arguments, $type, $isDefinedTest, $ignoreStrictCheck);
        } else {
            return parent::getAttribute($object, $item, $arguments, $type, $isDefinedTest, $ignoreStrictCheck);
        }
    }
}

class Twig_TemplateArrayAccessObject implements ArrayAccess
{
    protected $protected = 'protected';

    public $attributes = array(
        'defined' => 'defined',
        'zero'    => 0,
        'null'    => null,
        '1'       => 1,
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
    public $defined = 'defined';

    public $attributes = array(
        'zero'    => 0,
        'null'    => null,
        '1'       => 1,
    );

    protected $protected = 'protected';

    public function __isset($name)
    {
        return array_key_exists($name, $this->attributes);
    }

    public function __get($name)
    {
        return array_key_exists($name, $this->attributes) ? $this->attributes[$name] : null;
    }
}

class Twig_TemplatePropertyObject
{
    public $defined = 'defined';
    public $zero    = 0;
    public $null    = null;

    protected $protected = 'protected';
}

class Twig_TemplatePropertyObjectAndIterator extends Twig_TemplatePropertyObject implements IteratorAggregate
{
    public function getIterator()
    {
        return new ArrayIterator(array('foo', 'bar'));
    }
}

class Twig_TemplateMethodObject
{
    public function getDefined()
    {
        return 'defined';
    }

    public function get1()
    {
        return 1;
    }

    public function getZero()
    {
        return 0;
    }

    public function getNull()
    {
        return null;
    }

    protected function getProtected()
    {
        return 'protected';
    }

    static public function getStatic()
    {
        return 'static';
    }
}

class Twig_TemplateMethodAndPropObject
{
    private $a = 'a_prop';
    public function getA() {
        return 'a';
    }

    public $b = 'b_prop';
    public function getB() {
        return 'b';
    }

    private $c = 'c_prop';
    private function getC() {
        return 'c';
    }
}

class Twig_TemplateMagicMethodObject
{
    public function __call($method, $arguments) {
        return '__call_'.$method;
    }
}
