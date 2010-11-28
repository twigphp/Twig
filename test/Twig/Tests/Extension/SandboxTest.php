<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Twig_Tests_Extension_SandboxTest extends PHPUnit_Framework_TestCase
{
    static protected $params, $templates;

    public function setUp()
    {
        self::$params = array(
            'name' => 'Fabien',
            'obj'  => new Object(),
            'arr'  => array('obj' => new Object()),
        );

        self::$templates = array(
            '1_basic1' => '{{ obj.foo }}',
            '1_basic2' => '{{ name|upper }}',
            '1_basic3' => '{% if name %}foo{% endif %}',
            '1_basic4' => '{{ obj.bar }}',
            '1_basic5' => '{{ obj }}',
            '1_basic6' => '{{ arr.obj }}',
            '1_basic'  => '{% if obj.foo %}{{ obj.foo|upper }}{% endif %}',
        );
    }

    public function testSandboxGloballySet()
    {
        $twig = $this->getEnvironment(false, self::$templates);
        $this->assertEquals('FOO', $twig->loadTemplate('1_basic')->render(self::$params), 'Sandbox does nothing if it is disabled globally');

        $twig = $this->getEnvironment(true, self::$templates);
        try {
            $twig->loadTemplate('1_basic1')->render(self::$params);
            $this->fail('Sandbox throws a SecurityError exception if an unallowed method is called');
        } catch (Twig_Sandbox_SecurityError $e) {
        }

        $twig = $this->getEnvironment(true, self::$templates);
        try {
            $twig->loadTemplate('1_basic2')->render(self::$params);
            $this->fail('Sandbox throws a SecurityError exception if an unallowed filter is called');
        } catch (Twig_Sandbox_SecurityError $e) {
        }

        $twig = $this->getEnvironment(true, self::$templates);
        try {
            $twig->loadTemplate('1_basic3')->render(self::$params);
            $this->fail('Sandbox throws a SecurityError exception if an unallowed tag is used in the template');
        } catch (Twig_Sandbox_SecurityError $e) {
        }

        $twig = $this->getEnvironment(true, self::$templates);
        try {
            $twig->loadTemplate('1_basic4')->render(self::$params);
            $this->fail('Sandbox throws a SecurityError exception if an unallowed property is called in the template');
        } catch (Twig_Sandbox_SecurityError $e) {
        }

        $twig = $this->getEnvironment(true, self::$templates);
        try {
            $twig->loadTemplate('1_basic5')->render(self::$params);
            $this->fail('Sandbox throws a SecurityError exception if an unallowed method (__toString()) is called in the template');
        } catch (Twig_Sandbox_SecurityError $e) {
        }

        $twig = $this->getEnvironment(true, self::$templates);
        try {
            $twig->loadTemplate('1_basic6')->render(self::$params);
            $this->fail('Sandbox throws a SecurityError exception if an unallowed method (__toString()) is called in the template');
        } catch (Twig_Sandbox_SecurityError $e) {
        }

        $twig = $this->getEnvironment(true, self::$templates, array(), array(), array('Object' => 'foo'));
        $this->assertEquals('foo', $twig->loadTemplate('1_basic1')->render(self::$params), 'Sandbox allow some methods');

        $twig = $this->getEnvironment(true, self::$templates, array(), array(), array('Object' => '__toString'));
        $this->assertEquals('foo', $twig->loadTemplate('1_basic5')->render(self::$params), 'Sandbox allow some methods');

        $twig = $this->getEnvironment(true, self::$templates, array(), array('upper'));
        $this->assertEquals('FABIEN', $twig->loadTemplate('1_basic2')->render(self::$params), 'Sandbox allow some filters');

        $twig = $this->getEnvironment(true, self::$templates, array('if'));
        $this->assertEquals('foo', $twig->loadTemplate('1_basic3')->render(self::$params), 'Sandbox allow some tags');

        $twig = $this->getEnvironment(true, self::$templates, array(), array(), array(), array('Object' => 'bar'));
        $this->assertEquals('bar', $twig->loadTemplate('1_basic4')->render(self::$params), 'Sandbox allow some properties');
    }

    public function testSandboxLocallySetForAnInclude()
    {
        self::$templates = array(
            '2_basic'    => '{{ obj.foo }}{% include "2_included" %}{{ obj.foo }}',
            '2_included' => '{% if obj.foo %}{{ obj.foo|upper }}{% endif %}',
        );

        $twig = $this->getEnvironment(false, self::$templates);
        $this->assertEquals('fooFOOfoo', $twig->loadTemplate('2_basic')->render(self::$params), 'Sandbox does nothing if disabled globally and sandboxed not used for the include');

        self::$templates = array(
            '3_basic'    => '{{ obj.foo }}{% sandbox %}{% include "3_included" %}{% endsandbox %}{{ obj.foo }}',
            '3_included' => '{% if obj.foo %}{{ obj.foo|upper }}{% endif %}',
        );

        $twig = $this->getEnvironment(true, self::$templates);
        try {
            $twig->loadTemplate('3_basic')->render(self::$params);
            $this->fail('Sandbox throws a SecurityError exception when the included file is sandboxed');
        } catch (Twig_Sandbox_SecurityError $e) {
        }
    }

    protected function getEnvironment($sandboxed, $templates, $tags = array(), $filters = array(), $methods = array(), $properties = array())
    {
        $loader = new Twig_Loader_Array($templates);
        $twig = new Twig_Environment($loader, array('debug' => true, 'cache' => false));
        $policy = new Twig_Sandbox_SecurityPolicy($tags, $filters, $methods, $properties);
        $twig->addExtension(new Twig_Extension_Sandbox($policy, $sandboxed));

        return $twig;
    }
}

class Object
{
    public $bar = 'bar';

    public function __toString()
    {
        return 'foo';
    }

    public function foo()
    {
        return 'foo';
    }
}
