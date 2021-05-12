<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Tests\Extension;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Extension\StringLoaderExtension;

class StringLoaderExtensionTest extends TestCase
{
    public function testIncludeWithTemplateStringAndNoSandbox()
    {
        $twig = new Environment($this->createMock('\Twig\Loader\LoaderInterface'));
        $twig->addExtension(new StringLoaderExtension());
        $this->assertSame('something', twig_include($twig, [], twig_template_from_string($twig, 'something')));
    }
}
