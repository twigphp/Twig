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
use Twig\Loader\ArrayLoader;

/**
 * @group legacy
 */
class LegacyStringLoaderFunctionsTest extends TestCase
{
    public function testTemplateFromString()
    {
        $env = new Environment(new ArrayLoader());

        $this->assertSame(StringLoaderExtension::templateFromString($env, 'Foo')->render(), twig_template_from_string($env, 'Foo')->render());
    }
}
