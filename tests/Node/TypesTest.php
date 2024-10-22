<?php

namespace Twig\Tests\Node;

use Twig\Node\TypesNode;
use Twig\Test\NodeTestCase;

class TypesTest extends NodeTestCase
{
    private static function getValidMapping(): array
    {
        // {foo: 'string', bar?: 'number'}
        return [
            'foo' => [
                'type' => 'string',
                'optional' => false,
            ],
            'bar' => [
                'type' => 'number',
                'optional' => true,
            ],
        ];
    }

    public function testConstructor()
    {
        $types = self::getValidMapping();
        $node = new TypesNode($types, 1);

        $this->assertEquals($types, $node->getAttribute('mapping'));
    }

    public static function provideTests(): iterable
    {
        return [
            // 1st test: Node shouldn't compile at all
            [
                new TypesNode(self::getValidMapping(), 1),
                '',
            ],
        ];
    }
}
