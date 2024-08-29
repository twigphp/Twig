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
    private function getValidMapping()
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
        $types = $this->getValidMapping();
        $node = new TypesNode($types, 1);

        $this->assertEquals($types, $node->getNode('mapping'));
    }

    /** @return array<array<ArrayExpression>> */
    public function getInvalidMappings()
    {
        return [
            // {'foo': string}
            [
                new ArrayExpression([
                    new ConstantExpression('foo', 1),
                    new ConstantExpression('string', 1),
                ], 1),
                'Key at index 0 is not a NameExpression'
            ],

            // [13, 37]
            [
                new ArrayExpression([
                    new ConstantExpression(13, 1),
                    new ConstantExpression(37, 1),
                ], 1),
                'Key at index 0 is not a NameExpression'
            ],

            // {foo: bar}
            [
                new ArrayExpression([
                    new NameExpression('foo', 1),
                    new NameExpression('bar', 1),
                ], 1),
                'Value for key "foo" is not a ConstantExpression'
            ],

            // {foo: true}
            [
                new ArrayExpression([
                    new NameExpression('foo', 1),
                    new ConstantExpression(true, 1),
                ], 1),
                'Value for key "foo" is not a string'
            ],

            // {foo: 123}
            [
                new ArrayExpression([
                    new NameExpression('foo', 1),
                    new ConstantExpression(123, 1),
                ], 1),
                'Value for key "foo" is not a string'
            ],

            // {foo: {}}}
            [
                new ArrayExpression([
                    new NameExpression('foo', 1),
                    new ConstantExpression(new ArrayExpression([], 1), 1),
                ], 1),
                'Value for key "foo" is not a string'
            ],
        ];
    }

    /** @dataProvider getInvalidMappings */
    public function testConstructorThrowsOnInvalidMapping(ArrayExpression $mapping, string $message)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        new TypesNode($mapping, 1);
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
