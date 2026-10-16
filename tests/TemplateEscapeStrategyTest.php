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
        ]), ['autoescape' => $autoescape, 'cache' => false]);

        $this->assertSame($expected, $twig->load($name)->getDefaultEscapeStrategy());
    }

    public static function provideTemplates()
    {
        // compiled class names derive from the template name alone, so reusing a name across cases would silently reuse the first compiled class
        return [
            ['html', 'guessed_html.html.twig', 'name'],
            ['js', 'guessed_js.js.twig', 'name'],
            [false, 'guessed_none.txt.twig', 'name'],
            ['html', 'forced_html.js.twig', 'html'],
            [false, 'disabled.html.twig', false],
        ];
    }

    public function testTheStrategyDescribesTheBodyAndNotWhatTheTemplateRenders(): void
    {
        $twig = new Environment(new ArrayLoader([
            'inherits.html.twig' => "{% extends 'inherited.txt.twig' %}",
            'inherited.txt.twig' => '{{ value }}',
        ]), ['autoescape' => 'name', 'cache' => false]);

        $template = $twig->load('inherits.html.twig');

        $this->assertSame('html', $template->getDefaultEscapeStrategy());
        $this->assertSame('<b>', $template->render(['value' => '<b>']));
    }

    public function testAnAutoescapeTagWrappingTheWholeBodyDefinesTheStrategyOfTheTemplate(): void
    {
        $twig = new Environment(new ArrayLoader([
            'index.html.twig' => "{% autoescape 'js' %}{{ value }}{% endautoescape %}",
            'around_a_tag.html.twig' => "{% autoescape 'js' %}{% if value %}{{ value }}{% endif %}{% endautoescape %}",
            'surrounded_by_text.html.twig' => "<script>\n{% autoescape 'js' %}var x = {{ value }};{% endautoescape %}\n</script>",
            'nested.html.twig' => "{% autoescape 'js' %}{% autoescape 'css' %}{{ value }}{% endautoescape %}{% endautoescape %}",
            'disabled.html.twig' => '{% autoescape false %}{{ value }}{% endautoescape %}',
            'legacy_true.html.twig' => '{% autoescape true %}{{ value }}{% endautoescape %}',
        ]), ['autoescape' => 'name', 'cache' => false]);

        $this->assertSame('js', $twig->load('index.html.twig')->getDefaultEscapeStrategy());
        $this->assertSame('js', $twig->load('around_a_tag.html.twig')->getDefaultEscapeStrategy());
        $this->assertSame('js', $twig->load('surrounded_by_text.html.twig')->getDefaultEscapeStrategy());
        $this->assertSame('css', $twig->load('nested.html.twig')->getDefaultEscapeStrategy());
        $this->assertFalse($twig->load('disabled.html.twig')->getDefaultEscapeStrategy());
        $this->assertFalse($twig->load('legacy_true.html.twig')->getDefaultEscapeStrategy());
    }

    public function testAnAutoescapeTagCoveringPartOfTheBodyDoesNotChangeTheStrategyOfTheTemplate(): void
    {
        $twig = new Environment(new ArrayLoader([
            'sibling_print.html.twig' => "{{ value }}{% autoescape 'js' %}{{ value }}{% endautoescape %}",
            'sibling_tag.html.twig' => "{% if value %}{{ value }}{% endif %}{% autoescape 'js' %}{{ value }}{% endautoescape %}",
            'two_tags.html.twig' => "{% autoescape 'js' %}{{ value }}{% endautoescape %}{% autoescape 'css' %}{{ value }}{% endautoescape %}",
            'inside_a_tag.html.twig' => "{% if value %}{% autoescape 'js' %}{{ value }}{% endautoescape %}{% endif %}",
        ]), ['autoescape' => 'name', 'cache' => false]);

        $this->assertSame('html', $twig->load('sibling_print.html.twig')->getDefaultEscapeStrategy());
        $this->assertSame('html', $twig->load('sibling_tag.html.twig')->getDefaultEscapeStrategy());
        $this->assertSame('html', $twig->load('two_tags.html.twig')->getDefaultEscapeStrategy());
        $this->assertSame('html', $twig->load('inside_a_tag.html.twig')->getDefaultEscapeStrategy());
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
