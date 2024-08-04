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
use Twig\Node\Node;

class NodeTest extends TestCase
{
    public function testToString()
    {
        // callable is not a supported type for a Node attribute, but Drupal uses some apparently
        $node = new Node([], ['value' => function () { return '1'; }], 1);

        $this->assertEquals('Twig\Node\Node(value: \Closure)', (string) $node);
    }
}
