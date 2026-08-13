<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Extra\Markdown\Tests;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Extra\Markdown\DefaultMarkdown;
use Twig\Extra\Markdown\ErusevMarkdown;
use Twig\Extra\Markdown\LeagueMarkdown;
use Twig\Extra\Markdown\MarkdownExtension;
use Twig\Extra\Markdown\MarkdownInterface;
use Twig\Extra\Markdown\MarkdownRuntime;
use Twig\Extra\Markdown\MichelfMarkdown;
use Twig\Extra\Markdown\TempestMarkdown;
use Twig\Loader\ArrayLoader;
use Twig\RuntimeLoader\RuntimeLoaderInterface;

class FunctionalTest extends TestCase
{
    /**
     * @dataProvider getMarkdownTests
     */
    public function testMarkdown(string $template, string $expected): void
    {
        $classes = [LeagueMarkdown::class, ErusevMarkdown::class, /* MichelfMarkdown::class, */ DefaultMarkdown::class];
        if (class_exists(\Tempest\Markdown\Markdown::class)) {
            $classes[] = TempestMarkdown::class;
        }

        foreach ($classes as $class) {
            $twig = new Environment(new ArrayLoader([
                'index' => $template,
                'html' => <<<EOF
# Hello

Great!
EOF,
            ]));
            $twig->addExtension(new MarkdownExtension());
            $twig->addRuntimeLoader(new class($class) implements RuntimeLoaderInterface {
                private $class;

                public function __construct(string $class)
                {
                    $this->class = $class;
                }

                public function load(string $c): ?object
                {
                    return MarkdownRuntime::class === $c ? new $c(new $this->class()) : null;
                }
            });
            $this->assertMatchesRegularExpression('{'.$expected.'}m', trim($twig->render('index')));
        }
    }

    public static function getMarkdownTests()
    {
        return [
            [<<<EOF
{% apply markdown_to_html %}
# Hello

Great!
{% endapply %}
EOF, "<h1[^>]*>Hello</h1>\n+<p>Great!\s*</p>"],
            [<<<EOF
{% apply markdown_to_html %}
    # Hello

    Great!
{% endapply %}
EOF, "<h1[^>]*>Hello</h1>\n+<p>Great!\s*</p>"],
            ["{{ include('html')|markdown_to_html }}", "<h1[^>]*>Hello</h1>\n+<p>Great!\s*</p>"],
            [<<<EOF
{% apply markdown_to_html %}

Paragraph 1

Paragraph 2
{% endapply %}
EOF, "<p>Paragraph 1</p>\n+<p>Paragraph 2\s*</p>"],
        ];
    }

    /**
     * @dataProvider getIndentationTests
     */
    public function testStripsCommonIndentation(string $body, string $expected): void
    {
        $runtime = new MarkdownRuntime(new class implements MarkdownInterface {
            public function convert(string $body): string
            {
                return $body;
            }
        });

        $this->assertSame($expected, $runtime->convert($body));
    }

    public static function getIndentationTests()
    {
        return [
            'leading blank line keeps blank lines' => ["\nParagraph 1\n\nParagraph 2", "\nParagraph 1\n\nParagraph 2"],
            'common indentation is removed' => ["\n    Hello\n    =====\n\n    Great!\n", "\nHello\n=====\n\nGreat!\n"],
            'minimal common indentation is removed' => ["    a\n      b\n", "a\n  b\n"],
            'indented code block before non-indented prose is preserved' => ["    Code\n\nParagraph\n", "    Code\n\nParagraph\n"],
            'tab indentation is removed' => ["\tHello\n\tGreat!\n", "Hello\nGreat!\n"],
            'mixed tabs and spaces are left untouched' => ["\ta\n    b\n", "\ta\n    b\n"],
            'blank lines are ignored when computing indentation' => ["    a\n\n    b\n", "a\n\nb\n"],
        ];
    }

    public function testMarkdownToHtmlIsNotSafeInJsContext(): void
    {
        $twig = new Environment(new ArrayLoader([
            'index' => "{% autoescape 'js' %}{{ '# Hello'|markdown_to_html }}{% endautoescape %}",
        ]));
        $twig->addExtension(new MarkdownExtension());
        $twig->addRuntimeLoader(new class implements RuntimeLoaderInterface {
            public function load(string $c): ?object
            {
                return MarkdownRuntime::class === $c ? new $c(new DefaultMarkdown()) : null;
            }
        });

        $output = $twig->render('index');

        $this->assertStringNotContainsString('<h1>', $output);
        $this->assertMatchesRegularExpression('{\\\\u003[Cc]h1\\\\u003[Ee]Hello\\\\u003[Cc]\\\\/h1\\\\u003[Ee]}', $output);
    }
}
