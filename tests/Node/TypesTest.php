<?php

namespace Twig\Tests\Node;

use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\NameExpression;
use Twig\Node\TypesNode;
use Twig\Parser;
use Twig\Source;
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

    /** @dataProvider getMappingTests */
    public function testMappingParsing(string $template, ArrayExpression $expected): void
    {
        $env = new Environment(new ArrayLoader(), ['cache' => false, 'autoescape' => false]);
        $stream = $env->tokenize($source = new Source($template, ''));
        $parser = new Parser($env);
        $expected->setSourceContext($source);

        $typesNode = $parser->parse($stream)->getNode('body')->getNode('0');

        self::assertEquals($expected, $typesNode->getNode('mapping'));
    }

    public function getMappingTests(): array
    {
        return [
            // empty mapping
            [
                '{% types {} %}',
                new ArrayExpression([], 1),
            ],

            // simple
            [
                '{% types {foo: "bar"} %}',
                new ArrayExpression([
                    $this->createNameExpression('foo', false),
                    new ConstantExpression('bar', 1),
                ], 1),
                ['foo' => 'bar'],
            ],

            // trailing comma
            [
                '{% types {foo: "bar",} %}',
                new ArrayExpression([
                    $this->createNameExpression('foo', false),
                    new ConstantExpression('bar', 1),
                ], 1),
                ['foo' => 'bar'],
            ],

            // optional name
            [
                '{% types {foo?: "bar"} %}',
                new ArrayExpression([
                    $this->createNameExpression('foo', true),
                    new ConstantExpression('bar', 1),
                ], 1),
                ['foo?' => 'bar'],
            ],

            // multiple pairs, duplicate values
            [
                '{% types {foo: "foo", bar?: "foo", baz: "baz"} %}',
                new ArrayExpression([
                    $this->createNameExpression('foo', false),
                    new ConstantExpression('foo', 1),

                    $this->createNameExpression('bar', true),
                    new ConstantExpression('foo', 1),

                    $this->createNameExpression('baz', false),
                    new ConstantExpression('baz', 1),
                ], 1),
                ['foo' => 'foo', 'bar?' => 'foo', 'baz' => 'baz'],
            ],
        ];
    }

    private function createNameExpression(string $name, bool $isOptional): NameExpression
    {
        $name = new NameExpression($name, 1);
        $name->setAttribute('is_optional', $isOptional);
        return $name;
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
