<?php

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

class NodeTest extends TestCase
{
    use ExpectDeprecationTrait;

    public function testToString()
    {
        // callable is not a supported type for a Node attribute, but Drupal uses some apparently
        $node = new Node([], ['value' => function () { return '1'; }], 1);

        $this->assertEquals('Twig\Node\Node(value: \Closure)', (string) $node);
    }

    public function testAttributeDeprecationIgnore()
    {
        $node = new Node([], ['foo' => false]);
        $node->deprecateAttribute('foo', new NameDeprecation('foo/bar', '2.0', 'bar'));

        $this->assertSame(false, $node->getAttribute('foo', false));
    }

    /**
     * @group legacy
     */
    public function testAttributeDeprecationWithoutAlternative()
    {
        $node = new Node([], ['foo' => false]);
        $node->deprecateAttribute('foo', new NameDeprecation('foo/bar', '2.0'));

        $this->expectDeprecation('Since foo/bar 2.0: Getting attribute "foo" on a "Twig\Node\Node" class is deprecated.');
        $this->assertSame(false, $node->getAttribute('foo'));
    }

    /**
     * @group legacy
     */
    public function testAttributeDeprecationWithAlternative()
    {
        $node = new Node([], ['foo' => false]);
        $node->deprecateAttribute('foo', new NameDeprecation('foo/bar', '2.0', 'bar'));

        $this->expectDeprecation('Since foo/bar 2.0: Getting attribute "foo" on a "Twig\Node\Node" class is deprecated, get the "bar" attribute instead.');
        $this->assertSame(false, $node->getAttribute('foo'));
    }

    public function testNodeDeprecationIgnore()
    {
        $node = new Node(['foo' => $foo = new Node()], []);
        $node->deprecateNode('foo', new NameDeprecation('foo/bar', '2.0'));

        $this->assertSame($foo, $node->getNode('foo', false));
    }

    /**
     * @group legacy
     */
    public function testNodeDeprecationWithoutAlternative()
    {
        $node = new Node(['foo' => $foo = new Node()], []);
        $node->deprecateNode('foo', new NameDeprecation('foo/bar', '2.0'));

        $this->expectDeprecation('Since foo/bar 2.0: Getting node "foo" on a "Twig\Node\Node" class is deprecated.');
        $this->assertSame($foo, $node->getNode('foo'));
    }

    /**
     * @group legacy
     */
    public function testNodeAttributeDeprecationWithAlternative()
    {
        $node = new Node(['foo' => $foo = new Node()], []);
        $node->deprecateNode('foo', new NameDeprecation('foo/bar', '2.0', 'bar'));

        $this->expectDeprecation('Since foo/bar 2.0: Getting node "foo" on a "Twig\Node\Node" class is deprecated, get the "bar" node instead.');
        $this->assertSame($foo, $node->getNode('foo'));
    }
}
