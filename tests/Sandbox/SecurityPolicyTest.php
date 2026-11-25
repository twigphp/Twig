<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Tests\Sandbox;

use PHPUnit\Framework\TestCase;
use Twig\Markup;
use Twig\Sandbox\SecurityNotAllowedMethodError;
use Twig\Sandbox\SecurityNotAllowedPropertyError;
use Twig\Sandbox\SecurityPolicy;

class SecurityPolicyTest extends TestCase
{
    public function testMethodNamesAreCaseInsensitive(): void
    {
        $policy = new SecurityPolicy([], [], [SecurityPolicyTestObject::class => ['getFoo']]);
        $object = new SecurityPolicyTestObject();

        $policy->checkMethodAllowed($object, 'getFoo');
        $policy->checkMethodAllowed($object, 'GETFOO');
        $policy->checkMethodAllowed($object, 'getfoo');

        $this->expectException(SecurityNotAllowedMethodError::class);
        $this->expectExceptionMessage('Calling "getbar" method on a "'.SecurityPolicyTestObject::class.'" object is not allowed.');
        $policy->checkMethodAllowed($object, 'getBar');
    }

    public function testAnAllowedMethodStaysAllowedAndADeniedOneStaysDenied(): void
    {
        $policy = new SecurityPolicy([], [], [SecurityPolicyTestObject::class => ['getFoo']]);
        $object = new SecurityPolicyTestObject();

        $policy->checkMethodAllowed($object, 'getFoo');
        $policy->checkMethodAllowed($object, 'getFoo');

        for ($i = 0; $i < 2; ++$i) {
            try {
                $policy->checkMethodAllowed($object, 'getBar');
                $this->fail('A denied method must be denied on every call.');
            } catch (SecurityNotAllowedMethodError $e) {
                $this->assertSame(SecurityPolicyTestObject::class, $e->getClassName());
                $this->assertSame('getbar', $e->getMethodName());
            }
        }
    }

    public function testMethodsAllowedOnAClassAreAllowedOnItsSubclassesOnly(): void
    {
        $policy = new SecurityPolicy([], [], [SecurityPolicyTestObject::class => ['getFoo']]);

        $policy->checkMethodAllowed(new SecurityPolicyTestChildObject(), 'getFoo');

        $this->expectException(SecurityNotAllowedMethodError::class);
        $policy->checkMethodAllowed(new SecurityPolicyTestSiblingObject(), 'getFoo');
    }

    public function testSetAllowedMethodsRevokesWhatWasAllowedBefore(): void
    {
        $policy = new SecurityPolicy([], [], [SecurityPolicyTestObject::class => ['getFoo']]);
        $object = new SecurityPolicyTestObject();

        $policy->checkMethodAllowed($object, 'getFoo');

        $policy->setAllowedMethods([SecurityPolicyTestObject::class => ['getBar']]);
        $policy->checkMethodAllowed($object, 'getBar');

        $this->expectException(SecurityNotAllowedMethodError::class);
        $policy->checkMethodAllowed($object, 'getFoo');
    }

    public function testTemplateAndMarkupMethodsAreAllowedExceptTheConstructorAndWithContent(): void
    {
        $policy = new SecurityPolicy();
        $markup = new Markup('content', 'UTF-8');

        $policy->checkMethodAllowed($markup, '__toString');
        $policy->checkMethodAllowed($markup, '__toString');

        foreach (['__construct', '__CONSTRUCT', 'withContent'] as $method) {
            try {
                $policy->checkMethodAllowed($markup, $method);
                $this->fail(\sprintf('The "%s" method must not be allowed on a Markup object.', $method));
            } catch (SecurityNotAllowedMethodError $e) {
                $this->assertSame(strtolower($method), $e->getMethodName());
            }
        }
    }

    public function testAnAllowedPropertyStaysAllowedAndADeniedOneStaysDenied(): void
    {
        $policy = new SecurityPolicy([], [], [], [SecurityPolicyTestObject::class => ['foo']]);
        $object = new SecurityPolicyTestObject();

        $policy->checkPropertyAllowed($object, 'foo');
        $policy->checkPropertyAllowed($object, 'foo');

        for ($i = 0; $i < 2; ++$i) {
            try {
                $policy->checkPropertyAllowed($object, 'bar');
                $this->fail('A denied property must be denied on every call.');
            } catch (SecurityNotAllowedPropertyError $e) {
                $this->assertSame(SecurityPolicyTestObject::class, $e->getClassName());
                $this->assertSame('bar', $e->getPropertyName());
            }
        }
    }

    public function testPropertyNamesAreCaseSensitive(): void
    {
        $policy = new SecurityPolicy([], [], [], [SecurityPolicyTestObject::class => ['foo']]);
        $object = new SecurityPolicyTestObject();

        $policy->checkPropertyAllowed($object, 'foo');

        $this->expectException(SecurityNotAllowedPropertyError::class);
        $policy->checkPropertyAllowed($object, 'FOO');
    }

    public function testSetAllowedPropertiesRevokesWhatWasAllowedBefore(): void
    {
        $policy = new SecurityPolicy([], [], [], [SecurityPolicyTestObject::class => ['foo']]);
        $object = new SecurityPolicyTestObject();

        $policy->checkPropertyAllowed($object, 'foo');

        $policy->setAllowedProperties([SecurityPolicyTestObject::class => ['bar']]);
        $policy->checkPropertyAllowed($object, 'bar');

        $this->expectException(SecurityNotAllowedPropertyError::class);
        $policy->checkPropertyAllowed($object, 'foo');
    }

    public function testPropertyKeysThatAreNeitherStringsNorIntegersAreSupported(): void
    {
        $key = new Markup('foo', 'UTF-8');
        $policy = new SecurityPolicy([], [], [], [SecurityPolicyTestObject::class => [0, $key]]);
        $object = new SecurityPolicyTestObject();

        $policy->checkPropertyAllowed($object, 0);
        $policy->checkPropertyAllowed($object, 0);
        $policy->checkPropertyAllowed($object, $key);
        $policy->checkPropertyAllowed($object, $key);

        $this->expectException(SecurityNotAllowedPropertyError::class);
        $policy->checkPropertyAllowed($object, new Markup('bar', 'UTF-8'));
    }
}

class SecurityPolicyTestObject
{
    public $foo = 'foo';

    public function getFoo()
    {
        return 'foo';
    }

    public function getBar()
    {
        return 'bar';
    }
}

class SecurityPolicyTestChildObject extends SecurityPolicyTestObject
{
}

class SecurityPolicyTestSiblingObject
{
    public function getFoo()
    {
        return 'foo';
    }
}
