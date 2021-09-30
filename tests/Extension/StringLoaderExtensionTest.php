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
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Extension\StringLoaderExtension;

/**
 * Class StringLoaderExtensionTest
 * @package Twig\Tests\Extension
 */
class StringLoaderExtensionTest extends TestCase
{
    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function testIncludeWithTemplateStringAndNoSandbox()
    {
        $twig = new Environment($this->createMock('\Twig\Loader\LoaderInterface'));
        $twig->addExtension(new StringLoaderExtension());
        static::assertSame('something', twig_include($twig, [], twig_template_from_string($twig, 'something')->getTemplateName(), [], true,true, false));
    }
}
