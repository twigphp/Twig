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

use Twig\Node\TextNode;
use Twig\Test\NodeTestCase;

class TextTest extends NodeTestCase
{
    public function testConstructor()
    {
        $node = new TextNode('foo', 1);

        $this->assertEquals('foo', $node->getAttribute('data'));
    }

    public static function provideTests(): iterable
    {
        $tests = [];
        $tests[] = [new TextNode('foo', 1), "// line 1\nyield \"foo\";"];

        return $tests;
    }

    /**
     * @dataProvider getIsBlankData
     */
    public function testIsBlank($blank)
    {
        $this->isTrue((new TextNode($blank, 1))->isBlank());
        $this->isTrue((new TextNode(\chr(0xEF).\chr(0xBB).\chr(0xBF).$blank, 1))->isBlank());
    }

    public static function getIsBlankData()
    {
        return [
            [' '],
            ["\t"],
            ["\n"],
            ["\n\t\n   "],
        ];
    }
}
