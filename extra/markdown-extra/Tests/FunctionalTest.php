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
use Twig\Extra\Markdown\MarkdownRuntime;
use Twig\Extra\Markdown\MichelfMarkdown;
use Twig\Loader\ArrayLoader;
use Twig\RuntimeLoader\RuntimeLoaderInterface;

class FunctionalTest extends TestCase
{
    /**
     * @dataProvider getMarkdownTests
     */
    public function testMarkdown(string $markdown, string $expected): void
    {
        foreach ([LeagueMarkdown::class, ErusevMarkdown::class, /*MichelfMarkdown::class,*/ DefaultMarkdown::class] as $class) {
            $twig = $this->getTwig($class, [
                'apply' => "{% apply markdown_to_html %}\n{$markdown}\n{% endapply %}",
                'include' => "{{ include('md')|markdown_to_html }}",
                'indent' => "{{ include('indent_md')|markdown_to_html }}",
                'md' => $markdown,
                'indent_md' => ltrim(str_replace("\n", "\n\t", "\n$markdown"), "\n"),
            ]);

            $twig_md = trim($twig->render('apply'));
            $this->assertMatchesRegularExpression('{'.$expected.'}m', $twig_md);

            $twig_md = trim($twig->render('include'));
            $this->assertMatchesRegularExpression('{'.$expected.'}m', $twig_md);

            $twig_md = trim($twig->render('indent'));
            $this->assertMatchesRegularExpression('{'.$expected.'}m', $twig_md);

            $lib_md = trim((new $class)->convert($markdown));
            $this->assertEquals($lib_md, $twig_md, "Twig output versus {$class} output.");
        }
    }

    public function getMarkdownTests()
    {
        return [
            [<<<EOF
Hello
=====

Great!
EOF
            , "<h1>Hello</h1>\n+<p>Great!</p>"],

            [<<<EOF

Leading

Linebreak
EOF
            , "<p>Leading</p>\n+<p>Linebreak</p>"],

            [<<<EOF
    Code

Paragraph
EOF
            , "<pre><code>Code\n?</code></pre>\n+<p>Paragraph</p>"],
        ];
    }

    private function getTwig(string $class, array $templates): Environment
    {
        $twig = new Environment(new ArrayLoader($templates));
        $twig->addExtension(new MarkdownExtension());
        $twig->addRuntimeLoader(new class($class) implements RuntimeLoaderInterface {
            private $class;

            public function __construct(string $class)
            {
                $this->class = $class;
            }

            public function load($c)
            {
                if (MarkdownRuntime::class === $c)
                {
                    return new $c(new $this->class());
                }
            }
        });
        return $twig;
    }
}
