<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Extra\TwigExtraBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class IntegrationTest extends KernelTestCase
{
    public function testCommonMarkRendering()
    {
        self::bootKernel();

        $container = method_exists(self::class, 'getContainer') ? self::getContainer() : self::$container;
        $rendered = $container->get('twig')->render('markdown_to_html.html.twig');

        $this->assertStringContainsString('<h1>Hello <del>World</del></h1>', $rendered);
    }
}
