<?php

namespace Twig\Tests\Node\Expression;

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Twig\Extension\TypeOptimizerExtension;
use Twig\Loader\LoaderInterface;
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\GetAttrExpression;
use Twig\Node\Expression\NameExpression;
use Twig\Source;
use Twig\Template;
use Twig\Test\NodeTestCase;

class GetAttrTest extends NodeTestCase
{
    public function testConstructor()
    {
        $expr = new NameExpression('foo', 1);
        $attr = new ConstantExpression('bar', 1);
        $args = new ArrayExpression([], 1);
        $args->addElement(new NameExpression('foo', 1));
        $args->addElement(new ConstantExpression('bar', 1));
        $node = new GetAttrExpression($expr, $attr, $args, Template::ARRAY_CALL, 1);

        $this->assertEquals($expr, $node->getNode('node'));
        $this->assertEquals($attr, $node->getNode('attribute'));
        $this->assertEquals($args, $node->getNode('arguments'));
        $this->assertEquals(Template::ARRAY_CALL, $node->getAttribute('type'));
    }

    public function getTests()
    {
        $tests = [];

        $expr = new NameExpression('foo', 1);
        $attr = new ConstantExpression('bar', 1);
        $args = new ArrayExpression([], 1);
        $node = new GetAttrExpression($expr, $attr, $args, Template::ANY_CALL, 1);
        $tests[] = [$node, sprintf('%s%s, "bar", [], "any", false, false, false, 1)', $this->getAttributeGetter(), $this->getVariableGetter('foo', 1))];

        $node = new GetAttrExpression($expr, $attr, $args, Template::ARRAY_CALL, 1);
        $tests[] = [$node, '(($__internal_%s = // line 1'."\n".
            '($context["foo"] ?? null)) && is_array($__internal_%s) || $__internal_%s instanceof ArrayAccess ? ($__internal_%s["bar"] ?? null) : null)', null, true, ];

        $args = new ArrayExpression([], 1);
        $args->addElement(new NameExpression('foo', 1));
        $args->addElement(new ConstantExpression('bar', 1));
        $node = new GetAttrExpression($expr, $attr, $args, Template::METHOD_CALL, 1);
        $tests[] = [$node, sprintf('%s%s, "bar", [%s, "bar"], "method", false, false, false, 1)', $this->getAttributeGetter(), $this->getVariableGetter('foo', 1), $this->getVariableGetter('foo'))];

        $optimizedEnv = $this->getEnvironment();
        $optimizedEnv->setExtensions([new TypeOptimizerExtension()]);
        $optimizedEnv->setLoader($this->createMock(LoaderInterface::class));

        $tests[] = [
            $optimizedEnv->parse(
                $optimizedEnv->tokenize(
                    new Source('{{ ({ bar: { baz: 42 } }).bar.baz|raw }}', 'index.twig')
                )
            )->getNode('body'),
            <<<'PHP'
// line 1
echo ((["bar" => ["baz" => 42]]["bar"] ?? null)["baz"] ?? null);
PHP,
            $optimizedEnv,
        ];

        $tests[] = [
            $optimizedEnv->parse(
                $optimizedEnv->tokenize(
                    new Source("{% set foo = { bar: { baz: 42 } } %}\n{{ foo.bar.baz|raw }}", 'index.twig')
                )
            )->getNode('body'),
            <<<'PHP'
// line 1
$context["foo"] = ["bar" => ["baz" => 42]];
// line 2
echo ((($context["foo"] ?? null)["bar"] ?? null)["baz"] ?? null);
PHP,
            $optimizedEnv,
        ];

        $tests[] = [
            $optimizedEnv->parse($optimizedEnv->tokenize(new Source(<<<'TWIG'
{% type obj "\\Twig\\Tests\\Node\\Expression\\ClassWithPublicProperty" %}
{{ obj.name|raw }}
TWIG, 'index.twig')))->getNode('body'),
            <<<'PHP'
// line 1
// line 2
echo ($context["obj"] ?? null)?->name;
PHP,
            $optimizedEnv,
        ];

        $tests[] = [
            $optimizedEnv->parse($optimizedEnv->tokenize(new Source(<<<'TWIG'
{% type obj "\\Twig\\Tests\\Node\\Expression\\ClassWithPublicGetter" %}
{{ obj.name|raw }}
TWIG, 'index.twig')))->getNode('body'),
            <<<'PHP'
// line 1
// line 2
echo (($context["obj"] ?? null)?->getname());
PHP,
            $optimizedEnv,
        ];

        $tests[] = [
            $optimizedEnv->parse($optimizedEnv->tokenize(new Source(<<<'TWIG'
{% type obj "\\Twig\\Tests\\Node\\Expression\\ClassWithPublicFactory" %}
{{ obj.byName("foobar")|raw }}
TWIG, 'index.twig')))->getNode('body'),
            <<<'PHP'
// line 1
// line 2
echo (($context["obj"] ?? null)?->byName("foobar"));
PHP,
            $optimizedEnv,
        ];

        $tests[] = [
            $optimizedEnv->parse($optimizedEnv->tokenize(new Source(<<<'TWIG'
{% type obj "\\Twig\\Tests\\Node\\Expression\\ClassWithPublicComplexGetter" %}
{{ obj.instance.name|raw }}
TWIG, 'index.twig')))->getNode('body'),
            <<<'PHP'
// line 1
// line 2
echo ((($context["obj"] ?? null)?->getinstance())?->getname());
PHP,
            $optimizedEnv,
        ];

        $tests[] = [
            $optimizedEnv->parse($optimizedEnv->tokenize(new Source(<<<'TWIG'
{% type obj "\\Twig\\Tests\\Node\\Expression\\ClassWithPublicProperty|\\Twig\\Tests\\Node\\Expression\\ClassWithPublicGetter" %}
{{ obj.name|raw }}
TWIG, 'index.twig')))->getNode('body'),
            <<<'PHP'
// line 1
// line 2
echo match ([($context["obj"] ?? null), true][1]) {
($context["obj"] ?? null) instanceof \Twig\Tests\Node\Expression\ClassWithPublicProperty => ($context["obj"] ?? null)?->name;
($context["obj"] ?? null) instanceof \Twig\Tests\Node\Expression\ClassWithPublicGetter => (($context["obj"] ?? null)?->getname());
};
PHP,
            $optimizedEnv,
        ];

        return $tests;
    }
}

class ClassWithPublicProperty
{
    public function __construct(
        public string $name
    )
    {
    }
}

class ClassWithPublicGetter
{
    public function __construct(
        private string $name
    )
    {
    }

    public function getName(): string
    {
        return $this->name;
    }
}

class ClassWithPublicFactory
{
    public function byName(string $name): string
    {
        return $name;
    }
}

class ClassWithPublicComplexGetter
{
    public function __construct(
        private string $name
    )
    {
    }

    public function getInstance(): ClassWithPublicGetter
    {
        return new ClassWithPublicGetter($this->name);
    }
}
