<?php

namespace Twig\Tests;

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Twig\Environment;
use Twig\Event\PreRenderEvent;
use Twig\Loader\ArrayLoader;
use Twig\Loader\LoaderInterface;

class TemplateWrapperTest extends TestCase
{
    public function testHasGetBlocks()
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

    public function testRenderBlock()
    {
        $twig = new Environment(new ArrayLoader([
            'index' => '{% block foo %}{{ foo }}{{ bar }}{% endblock %}',
        ]));
        $twig->addGlobal('bar', 'BAR');

        $wrapper = $twig->load('index');
        $this->assertEquals('FOOBAR', $wrapper->renderBlock('foo', ['foo' => 'FOO']));
    }

    public function testDisplayBlock()
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

    public function testAnEventIsFired()
    {
        $eventDispatcher = $this->createMock(EventDispatcher::class);
        $loader = $this->createMock(LoaderInterface::class);
        $twig = new Environment($loader, [], $eventDispatcher);
        $eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(new PreRenderEvent([]), 'twig.pre_render:index.twig');
        $template = new TemplateForTest($twig);
        $template->render([]);
    }
}
