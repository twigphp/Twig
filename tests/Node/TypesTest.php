<?php

namespace Twig\Tests\Node;

use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\NameExpression;
use Twig\Node\TypesNode;
use Twig\Test\NodeTestCase;

class TypesTest extends NodeTestCase
{
    /** @return ArrayExpression */
    private function createArrayExpression()
    {
        // {foo: 'string', bar: 'int'}
        return new ArrayExpression([
            new NameExpression('foo', 1),
            new ConstantExpression('string', 1),

            new NameExpression('bar', 1),
            new ConstantExpression('int', 1),
        ], 1);
    }

    public function testConstructor()
    {
        $types = $this->createArrayExpression();
        $node = new TypesNode($types, 1);

        $this->assertEquals($types, $node->getNode('mapping'));
    }

    public function getTests()
    {
        return [
            // 1st test: Node shouldn't compile at all
            [
                new TypesNode($this->createArrayExpression(), 1),
                ''
            ]
        ];
    }
}
