<?php

namespace Twig\Tests\TokenParser;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\NameExpression;
use Twig\Parser;
use Twig\Source;

class TypesTokenParserTest extends TestCase
{
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
}
