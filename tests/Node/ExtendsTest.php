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

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Loader\ArrayLoader;
use Twig\Loader\FilesystemLoader;
use Twig\Test\NodeTestCase;

class ExtendsTest extends NodeTestCase
{
    public function testErrorFromArrayLoader()
    {
        $twig = new Environment(new ArrayLoader([
            'index.twig' => '{% include "include.twig" %}',
            'include.twig' => $include = <<<EOF



            {% extends 'invalid.twig' %}
            EOF,
        ]), ['debug' => true]);
        try {
            $twig->render('index.twig');
            $this->fail('Expected LoaderError to be thrown');
        } catch (LoaderError $e) {
            $this->assertSame('Template "invalid.twig" is not defined.', $e->getRawMessage());
            $this->assertSame(4, $e->getTemplateLine());
            $this->assertSame('include.twig', $e->getSourceContext()->getName());
            $this->assertSame($include, $e->getSourceContext()->getCode());
        }
    }

    public function testErrorFromFilesystemLoader()
    {
        $twig = new Environment(new FilesystemLoader([
            $dir = dirname(__DIR__).'/Fixtures/templates',
        ]), ['debug' => true]);
        $include = file_get_contents($dir.'/include.twig');
        try {
            $twig->render('index.twig');
            $this->fail('Expected LoaderError to be thrown');
        } catch (LoaderError $e) {
            $this->assertStringContainsString('Unable to find template "invalid.twig"', $e->getRawMessage());
            $this->assertSame(4, $e->getTemplateLine());
            $this->assertSame('include.twig', $e->getSourceContext()->getName());
            $this->assertSame($include, $e->getSourceContext()->getCode());
        }
    }

    public static function provideTests(): iterable
    {
        return [];
    }
}
