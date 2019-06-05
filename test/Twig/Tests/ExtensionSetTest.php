<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use PHPUnit\Framework\TestCase;
use Twig\Extension\AbstractExtension;
use Twig\ExtensionSet;
use Twig\Node\Node;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

class Twig_Tests_ExtensionTest extends TestCase
{
    /**
     * @group legacy
     * @expectedDeprecation Overloading filter "foo" in extension "Twig\Extension\StagingExtension" (already defined in "TwigTestExtensionForExtensionSet") is deprecated since Twig 2.12 and will throw an exception in 3.0.
     */
    public function testFilterOverloading()
    {
        $set = new ExtensionSet();
        $set->addExtension(new TwigTestExtensionForExtensionSet());
        $set->addFilter(new TwigFilter('foo'));
        $set->getFilters();
    }

    /**
     * @group legacy
     * @expectedDeprecation Overloading function "foo" in extension "Twig\Extension\StagingExtension" (already defined in "TwigTestExtensionForExtensionSet") is deprecated since Twig 2.12 and will throw an exception in 3.0.
     */
    public function testFunctionOverloading()
    {
        $set = new ExtensionSet();
        $set->addExtension(new TwigTestExtensionForExtensionSet());
        $set->addFunction(new TwigFunction('foo'));
        $set->getFunctions();
    }

    /**
     * @group legacy
     * @expectedDeprecation Overloading test "foo" in extension "Twig\Extension\StagingExtension" (already defined in "TwigTestExtensionForExtensionSet") is deprecated since Twig 2.12 and will throw an exception in 3.0.
     */
    public function testTestOverloading()
    {
        $set = new ExtensionSet();
        $set->addExtension(new TwigTestExtensionForExtensionSet());
        $set->addTest(new TwigTest('foo'));
        $set->getTests();
    }

    /**
     * @group legacy
     * @expectedDeprecation Overloading tag "foo" in extension "Twig\Extension\StagingExtension" (already defined in "TwigTestExtensionForExtensionSet") is deprecated since Twig 2.12 and will throw an exception in 3.0.
     */
    public function testTagOverloading()
    {
        $set = new ExtensionSet();
        $set->addExtension(new TwigTestExtensionForExtensionSet());
        $set->addTokenParser(new TwigTestTokenParserForExtensionSet());
        $set->getTokenParsers();
    }

    /**
     * @group legacy
     * @expectedDeprecation Overloading unary operator "foo" in extension "TwigTestOperatorExtensionForExtensionSet" (already defined in "TwigTestExtensionForExtensionSet") is deprecated since Twig 2.12 and will throw an exception in 3.0.
     * @expectedDeprecation Overloading binary operator "foo" in extension "TwigTestOperatorExtensionForExtensionSet" (already defined in "TwigTestExtensionForExtensionSet") is deprecated since Twig 2.12 and will throw an exception in 3.0.
     */
    public function testUnaryOperatorOverloading()
    {
        $set = new ExtensionSet();
        $set->addExtension(new TwigTestExtensionForExtensionSet());
        $set->addExtension(new TwigTestOperatorExtensionForExtensionSet());
        $set->getUnaryOperators();
    }
}

class TwigTestExtensionForExtensionSet extends AbstractExtension
{
    public function getTokenParsers()
    {
        return [
            new TwigTestTokenParserForExtensionSet(),
        ];
    }

    public function getFilters()
    {
        return [
            new TwigFilter('foo'),
        ];
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('foo'),
        ];
    }

    public function getTests()
    {
        return [
            new TwigTest('foo'),
        ];
    }

    public function getOperators()
    {
        return [
            [
                'foo' => [],
            ],
            [
                'foo' => [],
            ],
        ];
    }
}

class TwigTestOperatorExtensionForExtensionSet extends AbstractExtension
{
    public function getOperators()
    {
        return [
            [
                'foo' => [],
            ],
            [
                'foo' => [],
            ],
        ];
    }
}

class TwigTestTokenParserForExtensionSet extends AbstractTokenParser
{
    public function parse(Token $token)
    {
        return new Node();
    }

    public function getTag()
    {
        return 'foo';
    }
}
