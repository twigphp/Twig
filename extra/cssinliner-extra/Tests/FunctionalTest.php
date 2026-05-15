<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Extra\CssInliner\Tests;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Extra\CssInliner\CssInlinerExtension;
use Twig\Loader\ArrayLoader;

class FunctionalTest extends TestCase
{
    public function testInlineCssIsNotSafeInJsContext()
    {
        $twig = new Environment(new ArrayLoader([
            'index' => "{% autoescape 'js' %}{% apply inline_css %}<p>x</p>{% endapply %}{% endautoescape %}",
        ]));
        $twig->addExtension(new CssInlinerExtension());

        $output = $twig->render('index');

        $this->assertStringNotContainsString('<p>', $output);
        $this->assertStringNotContainsString('</p>', $output);
        $this->assertMatchesRegularExpression('{\\\\u003[Cc]p\\\\u003[Ee]x\\\\u003[Cc]\\\\/p\\\\u003[Ee]}', $output);
    }

    public function testInlineCssPreEscapesUnsafeInput()
    {
        $twig = new Environment(new ArrayLoader([
            'index' => '{{ payload|inline_css }}',
        ]));
        $twig->addExtension(new CssInlinerExtension());

        $output = $twig->render('index', ['payload' => '<script>alert(1)</script>']);

        $this->assertStringNotContainsString('<script>', $output);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $output);
    }
}
