<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\SandboxExtension;
use Twig\Loader\ArrayLoader;
use Twig\Loader\LoaderInterface;
use Twig\Loader\SourceContextLoaderInterface;
use Twig\Node\Expression\GetAttrExpression;
use Twig\NodeVisitor\NodeVisitorInterface;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityPolicy;
use Twig\Template;

class Twig_Tests_TemplateTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @expectedException \LogicException
     */
    public function testDisplayBlocksAcceptTemplateOnlyAsBlocks()
    {
        $template = $this->getMockForAbstractClass('\Twig\Template', [], '', false);
        $template->displayBlock('foo', [], ['foo' => [new \stdClass(), 'foo']]);
    }

    /**
     * @dataProvider getAttributeExceptions
     */
    public function testGetAttributeExceptions($template, $message)
    {
        $templates = ['index' => $template];
        $env = new Environment(new ArrayLoader($templates), ['strict_variables' => true]);
        $template = $env->load('index');

        $context = [
            'string' => 'foo',
            'null' => null,
            'empty_array' => [],
            'array' => ['foo' => 'foo'],
            'array_access' => new Twig_TemplateArrayAccessObject(),
            'magic_exception' => new Twig_TemplateMagicPropertyObjectWithException(),
            'object' => new \stdClass(),
        ];

        try {
            $template->render($context);
            $this->fail('Accessing an invalid attribute should throw an exception.');
        } catch (RuntimeError $e) {
            $this->assertSame(sprintf($message, 'index'), $e->getMessage());
        }
    }

    public function getAttributeExceptions()
    {
        return [
            ['{{ string["a"] }}', 'Impossible to access a key ("a") on a string variable ("foo") in "%s" at line 1.'],
            ['{{ null["a"] }}', 'Impossible to access a key ("a") on a null variable in "%s" at line 1.'],
            ['{{ empty_array["a"] }}', 'Key "a" does not exist as the array is empty in "%s" at line 1.'],
            ['{{ array["a"] }}', 'Key "a" for array with keys "foo" does not exist in "%s" at line 1.'],
            ['{{ array_access["a"] }}', 'Key "a" in object with ArrayAccess of class "Twig_TemplateArrayAccessObject" does not exist in "%s" at line 1.'],
            ['{{ string.a }}', 'Impossible to access an attribute ("a") on a string variable ("foo") in "%s" at line 1.'],
            ['{{ string.a() }}', 'Impossible to invoke a method ("a") on a string variable ("foo") in "%s" at line 1.'],
            ['{{ null.a }}', 'Impossible to access an attribute ("a") on a null variable in "%s" at line 1.'],
            ['{{ null.a() }}', 'Impossible to invoke a method ("a") on a null variable in "%s" at line 1.'],
            ['{{ array.a() }}', 'Impossible to invoke a method ("a") on an array in "%s" at line 1.'],
            ['{{ empty_array.a }}', 'Key "a" does not exist as the array is empty in "%s" at line 1.'],
            ['{{ array.a }}', 'Key "a" for array with keys "foo" does not exist in "%s" at line 1.'],
            ['{{ attribute(array, -10) }}', 'Key "-10" for array with keys "foo" does not exist in "%s" at line 1.'],
            ['{{ array_access.a }}', 'Neither the property "a" nor one of the methods "a()", "geta()"/"isa()" or "__call()" exist and have public access in class "Twig_TemplateArrayAccessObject" in "%s" at line 1.'],
            ['{% from _self import foo %}{% macro foo(obj) %}{{ obj.missing_method() }}{% endmacro %}{{ foo(array_access) }}', 'Neither the property "missing_method" nor one of the methods "missing_method()", "getmissing_method()"/"ismissing_method()" or "__call()" exist and have public access in class "Twig_TemplateArrayAccessObject" in "%s" at line 1.'],
            ['{{ magic_exception.test }}', 'An exception has been thrown during the rendering of a template ("Hey! Don\'t try to isset me!") in "%s" at line 1.'],
            ['{{ object["a"] }}', 'Impossible to access a key "a" on an object of class "stdClass" that does not implement ArrayAccess interface in "%s" at line 1.'],
        ];
    }

    /**
     * @dataProvider getGetAttributeWithSandbox
     */
    public function testGetAttributeWithSandbox($object, $item, $allowed)
    {
        $twig = new Environment($this->getMockBuilder('\Twig\Loader\LoaderInterface')->getMock());
        $policy = new SecurityPolicy([], [], [/*method*/], [/*prop*/], []);
        $twig->addExtension(new SandboxExtension($policy, !$allowed));
        $template = new Twig_TemplateTest($twig);

        try {
            $template->getAttribute($object, $item, [], 'any');

            if (!$allowed) {
                $this->fail();
            } else {
                $this->addToAssertionCount(1);
            }
        } catch (SecurityError $e) {
            if ($allowed) {
                $this->fail();
            } else {
                $this->addToAssertionCount(1);
            }

            $this->assertContains('is not allowed', $e->getMessage());
        }
    }

    public function getGetAttributeWithSandbox()
    {
        return [
            [new Twig_TemplatePropertyObject(), 'defined', false],
            [new Twig_TemplatePropertyObject(), 'defined', true],
            [new Twig_TemplateMethodObject(), 'defined', false],
            [new Twig_TemplateMethodObject(), 'defined', true],
        ];
    }

    /**
     * @group legacy
     */
    public function testGetAttributeWithTemplateAsObject()
    {
        // to be removed in 2.0
        $twig = new Environment($this->getMockBuilder('Twig_TemplateTestLoaderInterface')->getMock());
        //$twig = new Environment($this->getMockBuilder('\Twig\Loader\LoaderInterface', '\Twig\Loader\SourceContextLoaderInterface')->getMock());

        $template = new Twig_TemplateTest($twig, 'index.twig');
        $template1 = new Twig_TemplateTest($twig, 'index1.twig');

        $this->assertInstanceOf('\Twig\Markup', $template->getAttribute($template1, 'string'));
        $this->assertEquals('some_string', $template->getAttribute($template1, 'string'));

        $this->assertInstanceOf('\Twig\Markup', $template->getAttribute($template1, 'true'));
        $this->assertEquals('1', $template->getAttribute($template1, 'true'));

        $this->assertInstanceOf('\Twig\Markup', $template->getAttribute($template1, 'zero'));
        $this->assertEquals('0', $template->getAttribute($template1, 'zero'));

        $this->assertNotInstanceof('\Twig\Markup', $template->getAttribute($template1, 'empty'));
        $this->assertSame('', $template->getAttribute($template1, 'empty'));

        $this->assertFalse($template->getAttribute($template1, 'env', [], Template::ANY_CALL, true));
        $this->assertFalse($template->getAttribute($template1, 'environment', [], Template::ANY_CALL, true));
        $this->assertFalse($template->getAttribute($template1, 'getEnvironment', [], Template::METHOD_CALL, true));
        $this->assertFalse($template->getAttribute($template1, 'displayWithErrorHandling', [], Template::METHOD_CALL, true));
    }

    /**
     * @group legacy
     * @expectedDeprecation Calling "string" on template "index1.twig" from template "index.twig" is deprecated since version 1.28 and won't be supported anymore in 2.0.
     * @expectedDeprecation Calling "string" on template "index1.twig" from template "index.twig" is deprecated since version 1.28 and won't be supported anymore in 2.0.
     * @expectedDeprecation Calling "true" on template "index1.twig" from template "index.twig" is deprecated since version 1.28 and won't be supported anymore in 2.0.
     * @expectedDeprecation Calling "true" on template "index1.twig" from template "index.twig" is deprecated since version 1.28 and won't be supported anymore in 2.0.
     * @expectedDeprecation Calling "zero" on template "index1.twig" from template "index.twig" is deprecated since version 1.28 and won't be supported anymore in 2.0.
     * @expectedDeprecation Calling "zero" on template "index1.twig" from template "index.twig" is deprecated since version 1.28 and won't be supported anymore in 2.0.
     * @expectedDeprecation Calling "empty" on template "index1.twig" from template "index.twig" is deprecated since version 1.28 and won't be supported anymore in 2.0.
     * @expectedDeprecation Calling "empty" on template "index1.twig" from template "index.twig" is deprecated since version 1.28 and won't be supported anymore in 2.0.
     * @expectedDeprecation Calling "renderBlock" on template "index.twig" from template "index.twig" is deprecated since version 1.28 and won't be supported anymore in 2.0. Use block("name") instead).
     * @expectedDeprecation Calling "displayBlock" on template "index.twig" from template "index.twig" is deprecated since version 1.28 and won't be supported anymore in 2.0. Use block("name") instead).
     * @expectedDeprecation Calling "hasBlock" on template "index.twig" from template "index.twig" is deprecated since version 1.28 and won't be supported anymore in 2.0. Use "block("name") is defined" instead).
     * @expectedDeprecation Calling "render" on template "index.twig" from template "index.twig" is deprecated since version 1.28 and won't be supported anymore in 2.0. Use include("index.twig") instead).
     * @expectedDeprecation Calling "display" on template "index.twig" from template "index.twig" is deprecated since version 1.28 and won't be supported anymore in 2.0. Use include("index.twig") instead).
     * @expectedDeprecation Calling "renderBlock" on template "index1.twig" from template "index.twig" is deprecated since version 1.28 and won't be supported anymore in 2.0. Use block("name", template) instead).
     * @expectedDeprecation Calling "displayBlock" on template "index1.twig" from template "index.twig" is deprecated since version 1.28 and won't be supported anymore in 2.0. Use block("name", template) instead).
     * @expectedDeprecation Calling "hasBlock" on template "index1.twig" from template "index.twig" is deprecated since version 1.28 and won't be supported anymore in 2.0. Use "block("name", template) is defined" instead).
     * @expectedDeprecation Calling "render" on template "index1.twig" from template "index.twig" is deprecated since version 1.28 and won't be supported anymore in 2.0. Use include("index1.twig") instead).
     * @expectedDeprecation Calling "display" on template "index1.twig" from template "index.twig" is deprecated since version 1.28 and won't be supported anymore in 2.0. Use include("index1.twig") instead).
     */
    public function testGetAttributeWithTemplateAsObjectForDeprecations()
    {
        // to be removed in 2.0
        $twig = new Environment($this->getMockBuilder('Twig_TemplateTestLoaderInterface')->getMock());
        //$twig = new Environment($this->getMockBuilder('\Twig\Loader\LoaderInterface', '\Twig\Loader\SourceContextLoaderInterface')->getMock());

        $template = new Twig_TemplateTest($twig, 'index.twig');
        $template1 = new Twig_TemplateTest($twig, 'index1.twig');

        $this->assertInstanceOf('\Twig\Markup', $template->getAttribute($template1, 'string'));
        $this->assertEquals('some_string', $template->getAttribute($template1, 'string'));

        $this->assertInstanceOf('\Twig\Markup', $template->getAttribute($template1, 'true'));
        $this->assertEquals('1', $template->getAttribute($template1, 'true'));

        $this->assertInstanceOf('\Twig\Markup', $template->getAttribute($template1, 'zero'));
        $this->assertEquals('0', $template->getAttribute($template1, 'zero'));

        $this->assertNotInstanceof('\Twig\Markup', $template->getAttribute($template1, 'empty'));
        $this->assertSame('', $template->getAttribute($template1, 'empty'));

        $blocks = ['name' => [$template1, 'block_name']];

        // trigger some deprecation notice messages to check them with @expectedDeprecation
        $template->getAttribute($template, 'renderBlock', ['name', [], $blocks]);
        $template->getAttribute($template, 'displayBlock', ['name', [], $blocks]);
        $template->getAttribute($template, 'hasBlock', ['name', []]);
        $template->getAttribute($template, 'render', [[]]);
        $template->getAttribute($template, 'display', [[]]);

        $template->getAttribute($template1, 'renderBlock', ['name', [], $blocks]);
        $template->getAttribute($template1, 'displayBlock', ['name', [], $blocks]);
        $template->getAttribute($template1, 'hasBlock', ['name', []]);
        $template->getAttribute($template1, 'render', [[]]);
        $template->getAttribute($template1, 'display', [[]]);

        $this->assertFalse($template->getAttribute($template1, 'env', [], Template::ANY_CALL, true));
        $this->assertFalse($template->getAttribute($template1, 'environment', [], Template::ANY_CALL, true));
        $this->assertFalse($template->getAttribute($template1, 'getEnvironment', [], Template::METHOD_CALL, true));
        $this->assertFalse($template->getAttribute($template1, 'displayWithErrorHandling', [], Template::METHOD_CALL, true));
    }

    /**
     * @group legacy
     * @expectedDeprecation Silent display of undefined block "unknown" in template "index.twig" is deprecated since version 1.29 and will throw an exception in 2.0. Use the "block('unknown') is defined" expression to test for block existence.
     * @expectedDeprecation Silent display of undefined block "unknown" in template "index.twig" is deprecated since version 1.29 and will throw an exception in 2.0. Use the "block('unknown') is defined" expression to test for block existence.
     */
    public function testRenderBlockWithUndefinedBlock()
    {
        $twig = new Environment($this->getMockBuilder('Twig_TemplateTestLoaderInterface')->getMock());

        $template = new Twig_TemplateTest($twig, 'index.twig');
        $template->renderBlock('unknown', []);
        $template->displayBlock('unknown', []);
    }

    public function testGetAttributeOnArrayWithConfusableKey()
    {
        $template = new Twig_TemplateTest(new Environment($this->getMockBuilder('\Twig\Loader\LoaderInterface')->getMock()));

        $array = ['Zero', 'One', -1 => 'MinusOne', '' => 'EmptyString', '1.5' => 'FloatButString', '01' => 'IntegerButStringWithLeadingZeros'];

        $this->assertSame('Zero', $array[false]);
        $this->assertSame('One', $array[true]);
        $this->assertSame('One', $array[1.5]);
        $this->assertSame('One', $array['1']);
        $this->assertSame('MinusOne', $array[-1.5]);
        $this->assertSame('FloatButString', $array['1.5']);
        $this->assertSame('IntegerButStringWithLeadingZeros', $array['01']);
        $this->assertSame('EmptyString', $array[null]);

        $this->assertSame('Zero', $template->getAttribute($array, false), 'false is treated as 0 when accessing an array (equals PHP behavior)');
        $this->assertSame('One', $template->getAttribute($array, true), 'true is treated as 1 when accessing an array (equals PHP behavior)');
        $this->assertSame('One', $template->getAttribute($array, 1.5), 'float is casted to int when accessing an array (equals PHP behavior)');
        $this->assertSame('One', $template->getAttribute($array, '1'), '"1" is treated as integer 1 when accessing an array (equals PHP behavior)');
        $this->assertSame('MinusOne', $template->getAttribute($array, -1.5), 'negative float is casted to int when accessing an array (equals PHP behavior)');
        $this->assertSame('FloatButString', $template->getAttribute($array, '1.5'), '"1.5" is treated as-is when accessing an array (equals PHP behavior)');
        $this->assertSame('IntegerButStringWithLeadingZeros', $template->getAttribute($array, '01'), '"01" is treated as-is when accessing an array (equals PHP behavior)');
        $this->assertSame('EmptyString', $template->getAttribute($array, null), 'null is treated as "" when accessing an array (equals PHP behavior)');
    }

    /**
     * @dataProvider getGetAttributeTests
     */
    public function testGetAttribute($defined, $value, $object, $item, $arguments, $type)
    {
        $template = new Twig_TemplateTest(new Environment($this->getMockBuilder('\Twig\Loader\LoaderInterface')->getMock()));

        $this->assertEquals($value, $template->getAttribute($object, $item, $arguments, $type));
    }

    /**
     * @dataProvider getGetAttributeTests
     */
    public function testGetAttributeStrict($defined, $value, $object, $item, $arguments, $type, $exceptionMessage = null)
    {
        $template = new Twig_TemplateTest(new Environment($this->getMockBuilder('\Twig\Loader\LoaderInterface')->getMock(), ['strict_variables' => true]));

        if ($defined) {
            $this->assertEquals($value, $template->getAttribute($object, $item, $arguments, $type));
        } else {
            if (method_exists($this, 'expectException')) {
                $this->expectException('\Twig\Error\RuntimeError');
                if (null !== $exceptionMessage) {
                    $this->expectExceptionMessage($exceptionMessage);
                }
            } else {
                $this->setExpectedException('\Twig\Error\RuntimeError', $exceptionMessage);
            }
            $this->assertEquals($value, $template->getAttribute($object, $item, $arguments, $type));
        }
    }

    /**
     * @dataProvider getGetAttributeTests
     */
    public function testGetAttributeDefined($defined, $value, $object, $item, $arguments, $type)
    {
        $template = new Twig_TemplateTest(new Environment($this->getMockBuilder('\Twig\Loader\LoaderInterface')->getMock()));

        $this->assertEquals($defined, $template->getAttribute($object, $item, $arguments, $type, true));
    }

    /**
     * @dataProvider getGetAttributeTests
     */
    public function testGetAttributeDefinedStrict($defined, $value, $object, $item, $arguments, $type)
    {
        $template = new Twig_TemplateTest(new Environment($this->getMockBuilder('\Twig\Loader\LoaderInterface')->getMock(), ['strict_variables' => true]));

        $this->assertEquals($defined, $template->getAttribute($object, $item, $arguments, $type, true));
    }

    public function testGetAttributeCallExceptions()
    {
        $template = new Twig_TemplateTest(new Environment($this->getMockBuilder('\Twig\Loader\LoaderInterface')->getMock()));

        $object = new Twig_TemplateMagicMethodExceptionObject();

        $this->assertNull($template->getAttribute($object, 'foo'));
    }

    public function getGetAttributeTests()
    {
        $array = [
            'defined' => 'defined',
            'zero' => 0,
            'null' => null,
            '1' => 1,
            'bar' => true,
            'baz' => 'baz',
            '09' => '09',
            '+4' => '+4',
        ];

        $objectArray = new Twig_TemplateArrayAccessObject();
        $arrayObject = new \ArrayObject($array);
        $stdObject = (object) $array;
        $magicPropertyObject = new Twig_TemplateMagicPropertyObject();
        $propertyObject = new Twig_TemplatePropertyObject();
        $propertyObject1 = new Twig_TemplatePropertyObjectAndIterator();
        $propertyObject2 = new Twig_TemplatePropertyObjectAndArrayAccess();
        $propertyObject3 = new Twig_TemplatePropertyObjectDefinedWithUndefinedValue();
        $methodObject = new Twig_TemplateMethodObject();
        $magicMethodObject = new Twig_TemplateMagicMethodObject();

        $anyType = Template::ANY_CALL;
        $methodType = Template::METHOD_CALL;
        $arrayType = Template::ARRAY_CALL;

        $basicTests = [
            // array(defined, value, property to fetch)
            [true,  'defined', 'defined'],
            [false, null,      'undefined'],
            [false, null,      'protected'],
            [true,  0,         'zero'],
            [true,  1,         1],
            [true,  1,         1.0],
            [true,  null,      'null'],
            [true,  true,      'bar'],
            [true,  'baz',     'baz'],
            [true,  '09',      '09'],
            [true,  '+4',      '+4'],
        ];
        $testObjects = [
            // array(object, type of fetch)
            [$array,               $arrayType],
            [$objectArray,         $arrayType],
            [$arrayObject,         $anyType],
            [$stdObject,           $anyType],
            [$magicPropertyObject, $anyType],
            [$methodObject,        $methodType],
            [$methodObject,        $anyType],
            [$propertyObject,      $anyType],
            [$propertyObject1,     $anyType],
            [$propertyObject2,     $anyType],
        ];

        $tests = [];
        foreach ($testObjects as $testObject) {
            foreach ($basicTests as $test) {
                // properties cannot be numbers
                if (($testObject[0] instanceof \stdClass || $testObject[0] instanceof Twig_TemplatePropertyObject) && is_numeric($test[2])) {
                    continue;
                }

                if ('+4' === $test[2] && $methodObject === $testObject[0]) {
                    continue;
                }

                $tests[] = [$test[0], $test[1], $testObject[0], $test[2], [], $testObject[1]];
            }
        }

        // additional properties tests
        $tests = array_merge($tests, [
            [true, null, $propertyObject3, 'foo', [], $anyType],
        ]);

        // additional method tests
        $tests = array_merge($tests, [
            [true, 'defined', $methodObject, 'defined',    [], $methodType],
            [true, 'defined', $methodObject, 'DEFINED',    [], $methodType],
            [true, 'defined', $methodObject, 'getDefined', [], $methodType],
            [true, 'defined', $methodObject, 'GETDEFINED', [], $methodType],
            [true, 'static',  $methodObject, 'static',     [], $methodType],
            [true, 'static',  $methodObject, 'getStatic',  [], $methodType],

            [true, '__call_undefined', $magicMethodObject, 'undefined', [], $methodType],
            [true, '__call_UNDEFINED', $magicMethodObject, 'UNDEFINED', [], $methodType],
        ]);

        // add the same tests for the any type
        foreach ($tests as $test) {
            if ($anyType !== $test[5]) {
                $test[5] = $anyType;
                $tests[] = $test;
            }
        }

        $methodAndPropObject = new Twig_TemplateMethodAndPropObject();

        // additional method tests
        $tests = array_merge($tests, [
            [true, 'a', $methodAndPropObject, 'a', [], $anyType],
            [true, 'a', $methodAndPropObject, 'a', [], $methodType],
            [false, null, $methodAndPropObject, 'a', [], $arrayType],

            [true, 'b_prop', $methodAndPropObject, 'b', [], $anyType],
            [true, 'b', $methodAndPropObject, 'B', [], $anyType],
            [true, 'b', $methodAndPropObject, 'b', [], $methodType],
            [true, 'b', $methodAndPropObject, 'B', [], $methodType],
            [false, null, $methodAndPropObject, 'b', [], $arrayType],

            [false, null, $methodAndPropObject, 'c', [], $anyType],
            [false, null, $methodAndPropObject, 'c', [], $methodType],
            [false, null, $methodAndPropObject, 'c', [], $arrayType],
        ]);

        $arrayAccess = new Twig_TemplateArrayAccess();
        $tests = array_merge($tests, [
            [true, ['foo' => 'bar'], $arrayAccess, 'vars', [], $anyType],
        ]);

        // tests when input is not an array or object
        $tests = array_merge($tests, [
            [false, null, 42, 'a', [], $anyType, 'Impossible to access an attribute ("a") on a integer variable ("42") in "index.twig".'],
            [false, null, 'string', 'a', [], $anyType, 'Impossible to access an attribute ("a") on a string variable ("string") in "index.twig".'],
            [false, null, [], 'a', [], $anyType, 'Key "a" does not exist as the array is empty in "index.twig".'],
        ]);

        return $tests;
    }

    /**
     * @expectedException \Twig\Error\RuntimeError
     */
    public function testGetIsMethods()
    {
        $getIsObject = new Twig_TemplateGetIsMethods();
        $template = new Twig_TemplateTest(new Environment($this->getMockBuilder('\Twig\Loader\LoaderInterface')->getMock(), ['strict_variables' => true]));
        // first time should not create a cache for "get"
        $this->assertNull($template->getAttribute($getIsObject, 'get'));
        // 0 should be in the method cache now, so this should fail
        $this->assertNull($template->getAttribute($getIsObject, 0));
    }
}

class Twig_TemplateTest extends Template
{
    private $name;

    public function __construct(Environment $env, $name = 'index.twig')
    {
        parent::__construct($env);
        self::$cache = [];
        $this->name = $name;
    }

    public function getZero()
    {
        return 0;
    }

    public function getEmpty()
    {
        return '';
    }

    public function getString()
    {
        return 'some_string';
    }

    public function getTrue()
    {
        return true;
    }

    public function getTemplateName()
    {
        return $this->name;
    }

    public function getDebugInfo()
    {
        return [];
    }

    protected function doGetParent(array $context)
    {
        return false;
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
    }

    public function getAttribute($object, $item, array $arguments = [], $type = Template::ANY_CALL, $isDefinedTest = false, $ignoreStrictCheck = false)
    {
        if (\function_exists('twig_template_get_attributes')) {
            return twig_template_get_attributes($this, $object, $item, $arguments, $type, $isDefinedTest, $ignoreStrictCheck);
        } else {
            return parent::getAttribute($object, $item, $arguments, $type, $isDefinedTest, $ignoreStrictCheck);
        }
    }

    public function block_name($context, array $blocks = [])
    {
    }
}

class Twig_TemplateArrayAccessObject implements \ArrayAccess
{
    protected $protected = 'protected';

    public $attributes = [
        'defined' => 'defined',
        'zero' => 0,
        'null' => null,
        '1' => 1,
        'bar' => true,
        'baz' => 'baz',
        '09' => '09',
        '+4' => '+4',
    ];

    public function offsetExists($name)
    {
        return \array_key_exists($name, $this->attributes);
    }

    public function offsetGet($name)
    {
        return \array_key_exists($name, $this->attributes) ? $this->attributes[$name] : null;
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

    public $attributes = [
        'zero' => 0,
        'null' => null,
        '1' => 1,
        'bar' => true,
        'baz' => 'baz',
        '09' => '09',
        '+4' => '+4',
    ];

    protected $protected = 'protected';

    public function __isset($name)
    {
        return \array_key_exists($name, $this->attributes);
    }

    public function __get($name)
    {
        return \array_key_exists($name, $this->attributes) ? $this->attributes[$name] : null;
    }
}

class Twig_TemplateMagicPropertyObjectWithException
{
    public function __isset($key)
    {
        throw new \Exception('Hey! Don\'t try to isset me!');
    }
}

class Twig_TemplatePropertyObject
{
    public $defined = 'defined';
    public $zero = 0;
    public $null = null;
    public $bar = true;
    public $baz = 'baz';

    protected $protected = 'protected';
}

class Twig_TemplatePropertyObjectAndIterator extends Twig_TemplatePropertyObject implements \IteratorAggregate
{
    public function getIterator()
    {
        return new \ArrayIterator(['foo', 'bar']);
    }
}

class Twig_TemplatePropertyObjectAndArrayAccess extends Twig_TemplatePropertyObject implements \ArrayAccess
{
    private $data = [
        'defined' => 'defined',
        'zero' => 0,
        'null' => null,
        'bar' => true,
        'foo' => true,
        'baz' => 'baz',
        'baf' => 'baf',
    ];

    public function offsetExists($offset)
    {
        return \array_key_exists($offset, $this->data);
    }

    public function offsetGet($offset)
    {
        return $this->offsetExists($offset) ? $this->data[$offset] : 'n/a';
    }

    public function offsetSet($offset, $value)
    {
    }

    public function offsetUnset($offset)
    {
    }
}

class Twig_TemplatePropertyObjectDefinedWithUndefinedValue
{
    public $foo;

    public function __construct()
    {
        $this->foo = @$notExist;
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

    public function get09()
    {
        return '09';
    }

    public function getZero()
    {
        return 0;
    }

    public function getNull()
    {
    }

    public function isBar()
    {
        return true;
    }

    public function isBaz()
    {
        return 'should never be returned';
    }

    public function getBaz()
    {
        return 'baz';
    }

    protected function getProtected()
    {
        return 'protected';
    }

    public static function getStatic()
    {
        return 'static';
    }
}

class Twig_TemplateGetIsMethods
{
    public function get()
    {
    }

    public function is()
    {
    }
}

class Twig_TemplateMethodAndPropObject
{
    private $a = 'a_prop';

    public function getA()
    {
        return 'a';
    }

    public $b = 'b_prop';

    public function getB()
    {
        return 'b';
    }

    private $c = 'c_prop';

    private function getC()
    {
        return 'c';
    }
}

class Twig_TemplateArrayAccess implements \ArrayAccess
{
    public $vars = [
        'foo' => 'bar',
    ];
    private $children = [];

    public function offsetExists($offset)
    {
        return \array_key_exists($offset, $this->children);
    }

    public function offsetGet($offset)
    {
        return $this->children[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->children[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->children[$offset]);
    }
}

class Twig_TemplateMagicMethodObject
{
    public function __call($method, $arguments)
    {
        return '__call_'.$method;
    }
}

class Twig_TemplateMagicMethodExceptionObject
{
    public function __call($method, $arguments)
    {
        throw new \BadMethodCallException(sprintf('Unknown method "%s".', $method));
    }
}

class CExtDisablingNodeVisitor implements NodeVisitorInterface
{
    public function enterNode(Twig_NodeInterface $node, Environment $env)
    {
        if ($node instanceof GetAttrExpression) {
            $node->setAttribute('disable_c_ext', true);
        }

        return $node;
    }

    public function leaveNode(Twig_NodeInterface $node, Environment $env)
    {
        return $node;
    }

    public function getPriority()
    {
        return 0;
    }
}

// to be removed in 2.0
interface Twig_TemplateTestLoaderInterface extends LoaderInterface, SourceContextLoaderInterface
{
}
