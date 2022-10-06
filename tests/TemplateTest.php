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
use Twig\Error\RuntimeError;
use Twig\Extension\SandboxExtension;
use Twig\Loader\ArrayLoader;
use Twig\Loader\LoaderInterface;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityPolicy;
use Twig\Source;
use Twig\Template;

class TemplateTest extends TestCase
{
    public function testDisplayBlocksAcceptTemplateOnlyAsBlocks()
    {
        $this->expectException(\LogicException::class);

        $twig = new Environment($this->createMock(LoaderInterface::class));
        $template = new TemplateForTest($twig);
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
            'array_access' => new TemplateArrayAccessObject(),
            'magic_exception' => new TemplateMagicPropertyObjectWithException(),
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
            ['{{ array_access["a"] }}', 'Key "a" in object with ArrayAccess of class "Twig\Tests\TemplateArrayAccessObject" does not exist in "%s" at line 1.'],
            ['{{ string.a }}', 'Impossible to access an attribute ("a") on a string variable ("foo") in "%s" at line 1.'],
            ['{{ string.a() }}', 'Impossible to invoke a method ("a") on a string variable ("foo") in "%s" at line 1.'],
            ['{{ null.a }}', 'Impossible to access an attribute ("a") on a null variable in "%s" at line 1.'],
            ['{{ null.a() }}', 'Impossible to invoke a method ("a") on a null variable in "%s" at line 1.'],
            ['{{ array.a() }}', 'Impossible to invoke a method ("a") on an array in "%s" at line 1.'],
            ['{{ empty_array.a }}', 'Key "a" does not exist as the array is empty in "%s" at line 1.'],
            ['{{ array.a }}', 'Key "a" for array with keys "foo" does not exist in "%s" at line 1.'],
            ['{{ attribute(array, -10) }}', 'Key "-10" for array with keys "foo" does not exist in "%s" at line 1.'],
            ['{{ array_access.a }}', 'Neither the property "a" nor one of the methods "a()", "geta()"/"isa()"/"hasa()" or "__call()" exist and have public access in class "Twig\Tests\TemplateArrayAccessObject" in "%s" at line 1.'],
            ['{% from _self import foo %}{% macro foo(obj) %}{{ obj.missing_method() }}{% endmacro %}{{ foo(array_access) }}', 'Neither the property "missing_method" nor one of the methods "missing_method()", "getmissing_method()"/"ismissing_method()"/"hasmissing_method()" or "__call()" exist and have public access in class "Twig\Tests\TemplateArrayAccessObject" in "%s" at line 1.'],
            ['{{ magic_exception.test }}', 'An exception has been thrown during the rendering of a template ("Hey! Don\'t try to isset me!") in "%s" at line 1.'],
            ['{{ object["a"] }}', 'Impossible to access a key "a" on an object of class "stdClass" that does not implement ArrayAccess interface in "%s" at line 1.'],
        ];
    }

    /**
     * @dataProvider getGetAttributeWithSandbox
     */
    public function testGetAttributeWithSandbox($object, $item, $allowed)
    {
        $twig = new Environment($this->createMock(LoaderInterface::class));
        $policy = new SecurityPolicy([], [], [/*method*/], [/*prop*/], []);
        $twig->addExtension(new SandboxExtension($policy, !$allowed));
        $template = new TemplateForTest($twig);

        try {
            twig_get_attribute($twig, $template->getSourceContext(), $object, $item, [], 'any', false, false, true);

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

            $this->assertStringContainsString('is not allowed', $e->getMessage());
        }
    }

    public function getGetAttributeWithSandbox()
    {
        return [
            [new TemplatePropertyObject(), 'defined', false],
            [new TemplatePropertyObject(), 'defined', true],
            [new TemplateMethodObject(), 'defined', false],
            [new TemplateMethodObject(), 'defined', true],
        ];
    }

    public function testRenderBlockWithUndefinedBlock()
    {
        $this->expectException(RuntimeError::class);
        $this->expectExceptionMessage('Block "unknown" on template "index.twig" does not exist in "index.twig".');

        $twig = new Environment($this->createMock(LoaderInterface::class));
        $template = new TemplateForTest($twig, 'index.twig');
        try {
            $template->renderBlock('unknown', []);
        } catch (\Exception $e) {
            ob_end_clean();

            throw $e;
        }
    }

    public function testDisplayBlockWithUndefinedBlock()
    {
        $this->expectException(RuntimeError::class);
        $this->expectExceptionMessage('Block "unknown" on template "index.twig" does not exist in "index.twig".');

        $twig = new Environment($this->createMock(LoaderInterface::class));
        $template = new TemplateForTest($twig, 'index.twig');
        $template->displayBlock('unknown', []);
    }

    public function testDisplayBlockWithUndefinedParentBlock()
    {
        $this->expectException(RuntimeError::class);
        $this->expectExceptionMessage('Block "foo" should not call parent() in "index.twig" as the block does not exist in the parent template "parent.twig"');

        $twig = new Environment($this->createMock(LoaderInterface::class));
        $template = new TemplateForTest($twig, 'parent.twig');
        $template->displayBlock('foo', [], ['foo' => [new TemplateForTest($twig, 'index.twig'), 'block_foo']], false);
    }

    public function testGetAttributeOnArrayWithConfusableKey()
    {
        $twig = new Environment($this->createMock(LoaderInterface::class));
        $template = new TemplateForTest($twig);

        $array = ['Zero', 'One', -1 => 'MinusOne', '' => 'EmptyString', '1.5' => 'FloatButString', '01' => 'IntegerButStringWithLeadingZeros'];

        $this->assertSame('Zero', $array[false]);
        $this->assertSame('One', $array[true]);
        if (\PHP_VERSION_ID < 80100) {
            // This line will trigger a deprecation warning on PHP 8.1.
            $this->assertSame('One', $array[1.5]);
        }
        $this->assertSame('One', $array['1']);
        if (\PHP_VERSION_ID < 80100) {
            // This line will trigger a deprecation warning on PHP 8.1.
            $this->assertSame('MinusOne', $array[-1.5]);
        }
        $this->assertSame('FloatButString', $array['1.5']);
        $this->assertSame('IntegerButStringWithLeadingZeros', $array['01']);
        $this->assertSame('EmptyString', $array[null]);

        $this->assertSame('Zero', twig_get_attribute($twig, $template->getSourceContext(), $array, false), 'false is treated as 0 when accessing an array (equals PHP behavior)');
        $this->assertSame('One', twig_get_attribute($twig, $template->getSourceContext(), $array, true), 'true is treated as 1 when accessing an array (equals PHP behavior)');
        $this->assertSame('One', twig_get_attribute($twig, $template->getSourceContext(), $array, 1.5), 'float is casted to int when accessing an array (equals PHP behavior)');
        $this->assertSame('One', twig_get_attribute($twig, $template->getSourceContext(), $array, '1'), '"1" is treated as integer 1 when accessing an array (equals PHP behavior)');
        $this->assertSame('MinusOne', twig_get_attribute($twig, $template->getSourceContext(), $array, -1.5), 'negative float is casted to int when accessing an array (equals PHP behavior)');
        $this->assertSame('FloatButString', twig_get_attribute($twig, $template->getSourceContext(), $array, '1.5'), '"1.5" is treated as-is when accessing an array (equals PHP behavior)');
        $this->assertSame('IntegerButStringWithLeadingZeros', twig_get_attribute($twig, $template->getSourceContext(), $array, '01'), '"01" is treated as-is when accessing an array (equals PHP behavior)');
        $this->assertSame('EmptyString', twig_get_attribute($twig, $template->getSourceContext(), $array, null), 'null is treated as "" when accessing an array (equals PHP behavior)');
    }

    /**
     * @dataProvider getGetAttributeTests
     */
    public function testGetAttribute($defined, $value, $object, $item, $arguments, $type)
    {
        $twig = new Environment($this->createMock(LoaderInterface::class));
        $template = new TemplateForTest($twig);

        $this->assertEquals($value, twig_get_attribute($twig, $template->getSourceContext(), $object, $item, $arguments, $type));
    }

    /**
     * @dataProvider getGetAttributeTests
     */
    public function testGetAttributeStrict($defined, $value, $object, $item, $arguments, $type, $exceptionMessage = null)
    {
        $twig = new Environment($this->createMock(LoaderInterface::class), ['strict_variables' => true]);
        $template = new TemplateForTest($twig);

        if ($defined) {
            $this->assertEquals($value, twig_get_attribute($twig, $template->getSourceContext(), $object, $item, $arguments, $type));
        } else {
            $this->expectException(RuntimeError::class);
            if (null !== $exceptionMessage) {
                $this->expectExceptionMessage($exceptionMessage);
            }
            $this->assertEquals($value, twig_get_attribute($twig, $template->getSourceContext(), $object, $item, $arguments, $type));
        }
    }

    /**
     * @dataProvider getGetAttributeTests
     */
    public function testGetAttributeDefined($defined, $value, $object, $item, $arguments, $type)
    {
        $twig = new Environment($this->createMock(LoaderInterface::class));
        $template = new TemplateForTest($twig);

        $this->assertEquals($defined, twig_get_attribute($twig, $template->getSourceContext(), $object, $item, $arguments, $type, true));
    }

    /**
     * @dataProvider getGetAttributeTests
     */
    public function testGetAttributeDefinedStrict($defined, $value, $object, $item, $arguments, $type)
    {
        $twig = new Environment($this->createMock(LoaderInterface::class), ['strict_variables' => true]);
        $template = new TemplateForTest($twig);

        $this->assertEquals($defined, twig_get_attribute($twig, $template->getSourceContext(), $object, $item, $arguments, $type, true));
    }

    public function testGetAttributeCallExceptions()
    {
        $twig = new Environment($this->createMock(LoaderInterface::class));
        $template = new TemplateForTest($twig);

        $object = new TemplateMagicMethodExceptionObject();

        $this->assertNull(twig_get_attribute($twig, $template->getSourceContext(), $object, 'foo'));
    }

    public function getGetAttributeTests()
    {
        $array = [
            'defined' => 'defined',
            'zero' => 0,
            'null' => null,
            '1' => 1,
            'bar' => true,
            'foo' => true,
            'baz' => 'baz',
            'baf' => 'baf',
            '09' => '09',
            '+4' => '+4',
        ];

        $objectArray = new TemplateArrayAccessObject();
        $arrayObject = new \ArrayObject($array);
        $stdObject = (object) $array;
        $magicPropertyObject = new TemplateMagicPropertyObject();
        $propertyObject = new TemplatePropertyObject();
        $propertyObject1 = new TemplatePropertyObjectAndIterator();
        $propertyObject2 = new TemplatePropertyObjectAndArrayAccess();
        $propertyObject3 = new TemplatePropertyObjectDefinedWithUndefinedValue();
        $methodObject = new TemplateMethodObject();
        $magicMethodObject = new TemplateMagicMethodObject();

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
            [true,  true,      'foo'],
            [true,  'baz',     'baz'],
            [true,  'baf',     'baf'],
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
                if (($testObject[0] instanceof \stdClass || $testObject[0] instanceof TemplatePropertyObject) && is_numeric($test[2])) {
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

        $methodAndPropObject = new TemplateMethodAndPropObject();

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

        $arrayAccess = new TemplateArrayAccess();
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

    public function testGetIsMethods()
    {
        $twig = new Environment($this->createMock(LoaderInterface::class));

        $getIsObject = new TemplateGetIsMethods();
        $template = new TemplateForTest($twig, 'index.twig');
        // first time should not create a cache for "get"
        $this->assertNull(twig_get_attribute($twig, $template->getSourceContext(), $getIsObject, 'get'));
        // 0 should be in the method cache now, so this should fail
        $this->assertNull(twig_get_attribute($twig, $template->getSourceContext(), $getIsObject, 0));
    }
}

class TemplateForTest extends Template
{
    private $name;

    public function __construct(Environment $env, $name = 'index.twig')
    {
        parent::__construct($env);
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

    public function getSourceContext()
    {
        return new Source('', $this->getTemplateName());
    }

    protected function doGetParent(array $context)
    {
        return false;
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
    }

    public function block_name($context, array $blocks = [])
    {
    }
}

class TemplateArrayAccessObject implements \ArrayAccess
{
    protected $protected = 'protected';

    public $attributes = [
        'defined' => 'defined',
        'zero' => 0,
        'null' => null,
        '1' => 1,
        'bar' => true,
        'foo' => true,
        'baz' => 'baz',
        'baf' => 'baf',
        '09' => '09',
        '+4' => '+4',
    ];

    public function offsetExists($name) : bool
    {
        return \array_key_exists($name, $this->attributes);
    }

    /**
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($name)
    {
        return \array_key_exists($name, $this->attributes) ? $this->attributes[$name] : null;
    }

    public function offsetSet($name, $value) : void
    {
    }

    public function offsetUnset($name) : void
    {
    }
}

class TemplateMagicPropertyObject
{
    public $defined = 'defined';

    public $attributes = [
        'zero' => 0,
        'null' => null,
        '1' => 1,
        'bar' => true,
        'foo' => true,
        'baz' => 'baz',
        'baf' => 'baf',
        '09' => '09',
        '+4' => '+4',
    ];

    protected $protected = 'protected';

    public function __isset($name): bool
    {
        return \array_key_exists($name, $this->attributes);
    }

    public function __get($name)
    {
        return \array_key_exists($name, $this->attributes) ? $this->attributes[$name] : null;
    }
}

class TemplateMagicPropertyObjectWithException
{
    public function __isset($key): bool
    {
        throw new \Exception('Hey! Don\'t try to isset me!');
    }
}

class TemplatePropertyObject
{
    public $defined = 'defined';
    public $zero = 0;
    public $null = null;
    public $bar = true;
    public $foo = true;
    public $baz = 'baz';
    public $baf = 'baf';

    protected $protected = 'protected';
}

class TemplatePropertyObjectAndIterator extends TemplatePropertyObject implements \IteratorAggregate
{
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator(['foo', 'bar']);
    }
}

class TemplatePropertyObjectAndArrayAccess extends TemplatePropertyObject implements \ArrayAccess
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

    public function offsetExists($offset) : bool
    {
        return \array_key_exists($offset, $this->data);
    }

    /**
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->offsetExists($offset) ? $this->data[$offset] : 'n/a';
    }

    public function offsetSet($offset, $value) : void
    {
    }

    public function offsetUnset($offset) : void
    {
    }
}

class TemplatePropertyObjectDefinedWithUndefinedValue
{
    public $foo;

    public function __construct()
    {
        $this->foo = @$notExist;
    }
}

class TemplateMethodObject
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

    public function hasFoo()
    {
        return true;
    }

    public function hasBaz()
    {
        return 'should never be returned (has)';
    }

    public function isBaz()
    {
        return 'should never be returned (is)';
    }

    public function getBaz()
    {
        return 'Baz';
    }

    public function baz()
    {
        return 'baz';
    }

    public function hasBaf()
    {
        return 'should never be returned (has)';
    }

    public function isBaf()
    {
        return 'baf';
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

class TemplateGetIsMethods
{
    public function get()
    {
    }

    public function is()
    {
    }
}

class TemplateMethodAndPropObject
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

class TemplateArrayAccess implements \ArrayAccess
{
    public $vars = [
        'foo' => 'bar',
    ];
    private $children = [];

    public function offsetExists($offset) : bool
    {
        return \array_key_exists($offset, $this->children);
    }

    /**
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->children[$offset];
    }

    public function offsetSet($offset, $value) : void
    {
        $this->children[$offset] = $value;
    }

    public function offsetUnset($offset) : void
    {
        unset($this->children[$offset]);
    }
}

class TemplateMagicMethodObject
{
    public function __call($method, $arguments)
    {
        return '__call_'.$method;
    }
}

class TemplateMagicMethodExceptionObject
{
    public function __call($method, $arguments)
    {
        throw new \BadMethodCallException(sprintf('Unknown method "%s".', $method));
    }
}
