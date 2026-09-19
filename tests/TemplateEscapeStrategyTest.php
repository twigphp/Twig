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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Source;
use Twig\Template;

class TemplateEscapeStrategyTest extends TestCase
{
    /**
     * @dataProvider provideTemplates
     */
    #[DataProvider('provideTemplates')]
    public function testCompiledTemplatesExposeTheirStrategy(string|false $expected, string $name, string|false $autoescape): void
    {
        $twig = new Environment(new ArrayLoader([
            $name => '{{ value }}',
            'fragment.html.twig' => 'fragment',
        ]), ['autoescape' => $autoescape, 'cache' => false]);

        $this->assertSame($expected, $twig->load($name)->getDefaultEscapeStrategy());
    }

    public static function provideTemplates()
    {
        // each case uses its own template name so that compiled classes are not shared
        return [
            ['html', 'guessed_html.html.twig', 'name'],
            ['js', 'guessed_js.js.twig', 'name'],
            [false, 'guessed_none.txt.twig', 'name'],
            ['html', 'forced_html.js.twig', 'html'],
            [false, 'disabled.html.twig', false],
        ];
    }

    public function testAnAutoescapeTagDoesNotChangeTheStrategyOfTheTemplate(): void
    {
        $twig = new Environment(new ArrayLoader([
            'index.html.twig' => "{% autoescape 'js' %}{{ value }}{% endautoescape %}",
        ]), ['autoescape' => 'name', 'cache' => false]);

        $this->assertSame('html', $twig->load('index.html.twig')->getDefaultEscapeStrategy());
    }

    public function testEmbeddedTemplatesExposeTheStrategyOfTheirTemplate(): void
    {
        $twig = new Environment(new ArrayLoader([
            'index.js.twig' => "{% embed 'layout.html.twig' %}{% block content %}{{ value }}{% endblock %}{% endembed %}",
            'layout.html.twig' => '{% block content %}{% endblock %}',
        ]), ['autoescape' => 'name', 'cache' => false]);

        $compiled = $twig->compileSource(new Source($twig->getLoader()->getSourceContext('index.js.twig')->getCode(), 'index.js.twig'));

        $this->assertSame(2, substr_count($compiled, 'public function getDefaultEscapeStrategy(): string|false'));
        $this->assertSame(2, substr_count($compiled, 'return "js";'));
    }

    public function testTemplatesCompiledBeforeTheStrategyWasExposedReportNoStrategy(): void
    {
        $twig = new Environment(new ArrayLoader(['index.html.twig' => '{{ value }}']), ['autoescape' => 'name', 'cache' => false]);

        $this->assertFalse((new TemplateWithoutStrategy($twig))->getDefaultEscapeStrategy());
    }
}

class TemplateWithoutStrategy extends Template
{
    public function getTemplateName(): string
    {
        return 'index.html.twig';
    }

    public function getDebugInfo(): array
    {
        return [];
    }

    public function getSourceContext(): Source
    {
        return new Source('', $this->getTemplateName());
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        yield '';
    }
}
