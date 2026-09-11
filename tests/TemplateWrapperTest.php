<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Tests;

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Loader\ArrayLoader;
use Twig\TwigFunction;

class TemplateWrapperTest extends TestCase
{
    public function testHasGetBlocks(): void
    {
        $twig = new Environment(new ArrayLoader([
            'index' => '{% block foo %}{% endblock %}',
            'index_with_use' => '{% use "imported" %}{% block foo %}{% endblock %}',
            'index_with_extends' => '{% extends "extended" %}{% block foo %}{% endblock %}',
            'imported' => '{% block imported %}{% endblock %}',
            'extended' => '{% block extended %}{% endblock %}',
        ]));

        $wrapper = $twig->load('index');
        $this->assertTrue($wrapper->hasBlock('foo'));
        $this->assertFalse($wrapper->hasBlock('bar'));
        $this->assertEquals(['foo'], $wrapper->getBlockNames());

        $wrapper = $twig->load('index_with_use');
        $this->assertTrue($wrapper->hasBlock('foo'));
        $this->assertTrue($wrapper->hasBlock('imported'));
        $this->assertEquals(['imported', 'foo'], $wrapper->getBlockNames());

        $wrapper = $twig->load('index_with_extends');
        $this->assertTrue($wrapper->hasBlock('foo'));
        $this->assertTrue($wrapper->hasBlock('extended'));
        $this->assertEquals(['foo', 'extended'], $wrapper->getBlockNames());
    }

    public function testBlockIntrospectionIncludesGlobals(): void
    {
        $twig = new Environment(new ArrayLoader([
            'index' => '{% extends layout %}',
            'global_parent' => '{% block global %}{% endblock %}',
            'local_parent' => '{% block local %}{% endblock %}',
        ]));
        $twig->addGlobal('layout', 'global_parent');

        $wrapper = $twig->load('index');
        $this->assertTrue($wrapper->hasBlock('global'));
        $this->assertFalse($wrapper->hasBlock('local'));
        $this->assertSame(['global'], $wrapper->getBlockNames());

        $context = ['layout' => 'local_parent'];
        $this->assertTrue($wrapper->hasBlock('local', $context));
        $this->assertFalse($wrapper->hasBlock('global', $context));
        $this->assertSame(['local'], $wrapper->getBlockNames($context));
    }

    public function testStreamBlockIncludesGlobals(): void
    {
        $twig = new Environment(new ArrayLoader([
            'index' => '{% extends layout %}',
            'layout' => '{% block foo %}{{ foo }}{{ bar }}{% endblock %}',
        ]));
        $twig->addGlobal('layout', 'layout');
        $twig->addGlobal('bar', 'BAR');

        $streamed = '';
        foreach ($twig->load('index')->streamBlock('foo', ['foo' => 'FOO']) as $data) {
            $streamed .= $data;
        }

        $this->assertSame('FOOBAR', $streamed);
    }

    /**
     * @dataProvider provideDynamicParentFailures
     */
    #[DataProvider('provideDynamicParentFailures')]
    public function testDynamicParentFailuresAreWrapped(string $template, ?TwigFunction $function, string $previousException): void
    {
        $twig = new Environment(new ArrayLoader(['index' => $template]));
        if (null !== $function) {
            $twig->addFunction($function);
        }

        foreach ([
            'block introspection' => static fn () => $twig->load('index')->getBlockNames(),
            'rendering' => static fn () => $twig->render('index'),
        ] as $entryPoint => $call) {
            try {
                $call();
                $this->fail(\sprintf('Resolving the dynamic parent during %s must fail.', $entryPoint));
            } catch (RuntimeError $e) {
                $this->assertSame('index', $e->getSourceContext()->getName());
                $this->assertSame(1, $e->getTemplateLine());
                $this->assertInstanceOf($previousException, $e->getPrevious());
            }
        }
    }

    public function testDynamicParentTwigErrorsAreNotWrapped(): void
    {
        $twig = new Environment(new ArrayLoader(['index' => '{% extends parent %}']));

        try {
            $twig->load('index')->getBlockNames(['parent' => 'missing']);
            $this->fail('Loading the dynamic parent must fail.');
        } catch (LoaderError $e) {
            $this->assertSame('index', $e->getSourceContext()->getName());
            $this->assertSame(1, $e->getTemplateLine());
            $this->assertNull($e->getPrevious());
        }
    }

    public static function provideDynamicParentFailures(): iterable
    {
        yield 'missing parent variable' => ['{% extends parent %}', null, \TypeError::class];
        yield 'exception thrown by parent expression' => ['{% extends boom() %}', new TwigFunction('boom', static function (): never { throw new \DomainException('kaboom'); }), \DomainException::class];
    }

    public function testBlockIntrospectionReportsTheExtendsLineForAMissingParent(): void
    {
        $twig = new Environment(new ArrayLoader(['index' => "\n\n{% extends 'missing' %}"]));

        try {
            $twig->load('index')->hasBlock('foo');
            $this->fail('Introspecting a template with a missing parent must fail.');
        } catch (LoaderError $e) {
            $this->assertSame('Template "missing" is not defined.', $e->getRawMessage());
            $this->assertSame(3, $e->getTemplateLine());
        }
    }

    public function testRenderBlock(): void
    {
        $twig = new Environment(new ArrayLoader([
            'index' => '{% block foo %}{{ foo }}{{ bar }}{% endblock %}',
        ]));
        $twig->addGlobal('bar', 'BAR');

        $wrapper = $twig->load('index');
        $this->assertEquals('FOOBAR', $wrapper->renderBlock('foo', ['foo' => 'FOO']));
    }

    public function testDisplayBlock(): void
    {
        $twig = new Environment(new ArrayLoader([
            'index' => '{% block foo %}{{ foo }}{{ bar }}{% endblock %}',
        ]));

        $twig->addGlobal('bar', 'BAR');

        $wrapper = $twig->load('index');

        ob_start();
        $wrapper->displayBlock('foo', ['foo' => 'FOO']);

        $this->assertEquals('FOOBAR', ob_get_clean());
    }
}
