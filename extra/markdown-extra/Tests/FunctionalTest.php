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
    public function testMarkdown(string $template, string $expected): void
    {
        foreach ([LeagueMarkdown::class, ErusevMarkdown::class, /* MichelfMarkdown::class, */ DefaultMarkdown::class] as $class) {
            $twig = new Environment(new ArrayLoader([
                'index' => $template,
                'html' => <<<EOF
Hello
=====

Great!
EOF
            ]));
            $twig->addExtension(new MarkdownExtension());
            $twig->addRuntimeLoader(new class($class) implements RuntimeLoaderInterface {
                private $class;

                public function __construct(string $class)
                {
                    $this->class = $class;
                }

                public function load($c)
                {
                    if (MarkdownRuntime::class === $c) {
                        return new $c(new $this->class());
                    }
                }
            });
            $this->assertMatchesRegularExpression('{'.$expected.'}m', trim($twig->render('index')));
        }
    }

    public function getMarkdownTests()
    {
        return [
            [<<<EOF
{% apply markdown_to_html %}
Hello
=====

Great!
{% endapply %}
EOF
                , "<h1>Hello</h1>\n+<p>Great!</p>"],
            [<<<EOF
{% apply markdown_to_html %}
    Hello
    =====

    Great!
{% endapply %}
EOF
                , "<h1>Hello</h1>\n+<p>Great!</p>"],
            ["{{ include('html')|markdown_to_html }}", "<h1>Hello</h1>\n+<p>Great!</p>"],
        ];
    }
}
