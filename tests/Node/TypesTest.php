<?php

namespace Twig\Tests\Node;

use Twig\Node\TypesNode;
use Twig\Test\NodeTestCase;

class TypesTest extends NodeTestCase
{
    private function getValidMapping(): array
    {
        // {foo: 'string', bar?: 'int'}
        return [
            'foo' => [
                'type' => 'string',
                'optional' => false,
            ],
            'bar' => [
                'type' => 'int',
                'optional' => true,
            ]
        ];
    }

    public function testConstructor()
    {
        $types = $this->getValidMapping();
        $node = new TypesNode($types, 1);

        $this->assertEquals($types, $node->getAttribute('mapping'));
    }

    public function getTests()
    {
        return [
            // 1st test: Node shouldn't compile at all
            [
                new TypesNode($this->getValidMapping(), 1),
                ''
            ]
        ];
    }
}
