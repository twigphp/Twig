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
use Twig\Extension\CoreExtension;
use Twig\Extension\StringLoaderExtension;
use Twig\Loader\ArrayLoader;

class StringLoaderExtensionTest extends TestCase
{
    public function testIncludeWithTemplateStringAndNoSandbox()
    {
        $twig = new Environment(new ArrayLoader());
        $twig->addExtension(new StringLoaderExtension());
        $this->assertSame('something', CoreExtension::include($twig, [], StringLoaderExtension::templateFromString($twig, 'something')));
    }
}
