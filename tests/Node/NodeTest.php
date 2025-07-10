<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Tests\Node;

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Twig\Node\NameDeprecation;
use Twig\Node\Node;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

class NodeTest extends TestCase
{
    use ExpectDeprecationTrait;

    public function testToString()
    {
        // callable is not a supported type for a Node attribute, but Drupal uses some apparently
        $node = new NodeForTest([], ['value' => function () { return '1'; }], 1);

        $this->assertEquals(<<<EOF
Twig\Tests\Node\NodeForTest
  attributes:
    value: \Closure
EOF
            , (string) $node
        );
    }

    public function testToStringWithTwigCallables()
    {
        $node = new NodeForTest([], [
            'function' => new TwigFunction('a_function'),
            'filter' => new TwigFilter('a_filter'),
            'test' => new TwigTest('a_test'),
        ], 1);

        $this->assertEquals(<<<EOF
Twig\Tests\Node\NodeForTest
  attributes:
    function: Twig\TwigFunction(a_function)
    filter: Twig\TwigFilter(a_filter)
    test: Twig\TwigTest(a_test)
EOF
            , (string) $node);
    }

    public function testToStringWithTag()
    {
        $node = new NodeForTest();
        $node->setNodeTag('tag');

        $this->assertEquals(<<<EOF
Twig\Tests\Node\NodeForTest
  tag: tag
EOF
            , (string) $node);
    }

    public function testAttributeDeprecationIgnore()
    {
        $node = new NodeForTest([], ['foo' => false]);
        $node->deprecateAttribute('foo', new NameDeprecation('foo/bar', '2.0', 'bar'));

        $this->assertFalse($node->getAttribute('foo', false));
    }

    /**
     * @group legacy
     */
    public function testAttributeDeprecationWithoutAlternative()
    {
        $node = new NodeForTest([], ['foo' => false]);
        $node->deprecateAttribute('foo', new NameDeprecation('foo/bar', '2.0'));

        $this->expectDeprecation('Since foo/bar 2.0: Getting attribute "foo" on a "Twig\Tests\Node\NodeForTest" class is deprecated.');
        $this->assertFalse($node->getAttribute('foo'));
    }

    /**
     * @group legacy
     */
    public function testAttributeDeprecationWithAlternative()
    {
        $node = new NodeForTest([], ['foo' => false]);
        $node->deprecateAttribute('foo', new NameDeprecation('foo/bar', '2.0', 'bar'));

        $this->expectDeprecation('Since foo/bar 2.0: Getting attribute "foo" on a "Twig\Tests\Node\NodeForTest" class is deprecated, get the "bar" attribute instead.');
        $this->assertFalse($node->getAttribute('foo'));
    }

    public function testNodeDeprecationIgnore()
    {
        $node = new NodeForTest(['foo' => $foo = new NodeForTest()]);
        $node->deprecateNode('foo', new NameDeprecation('foo/bar', '2.0'));

        $this->assertSame($foo, $node->getNode('foo', false));
    }

    /**
     * @group legacy
     */
    public function testNodeDeprecationWithoutAlternative()
    {
        $node = new NodeForTest(['foo' => $foo = new NodeForTest()]);
        $node->deprecateNode('foo', new NameDeprecation('foo/bar', '2.0'));

        $this->expectDeprecation('Since foo/bar 2.0: Getting node "foo" on a "Twig\Tests\Node\NodeForTest" class is deprecated.');
        $this->assertSame($foo, $node->getNode('foo'));
    }

    /**
     * @group legacy
     */
    public function testNodeAttributeDeprecationWithAlternative()
    {
        $node = new NodeForTest(['foo' => $foo = new NodeForTest()]);
        $node->deprecateNode('foo', new NameDeprecation('foo/bar', '2.0', 'bar'));

        $this->expectDeprecation('Since foo/bar 2.0: Getting node "foo" on a "Twig\Tests\Node\NodeForTest" class is deprecated, get the "bar" node instead.');
        $this->assertSame($foo, $node->getNode('foo'));
    }
}

class NodeForTest extends Node
{
}
