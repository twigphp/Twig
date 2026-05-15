<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Extra\Inky\Tests;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Extra\Inky\InkyExtension;
use Twig\Loader\ArrayLoader;

class FunctionalTest extends TestCase
{
    public function testInkyToHtmlPreEscapesUnsafeInput()
    {
        $twig = new Environment(new ArrayLoader([
            'index' => '{{ payload|inky_to_html }}',
        ]));
        $twig->addExtension(new InkyExtension());

        $output = $twig->render('index', ['payload' => '<script>alert(1)</script>']);

        $this->assertStringNotContainsString('<script>', $output);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $output);
    }
}
