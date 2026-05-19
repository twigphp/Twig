<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Tests;

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use PHPUnit\Framework\TestCase;
use Twig\Compiler;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

class CompilerTest extends TestCase
{
    public function testStringEncodesSingleQuotesAsHexEscape()
    {
        $compiler = new Compiler(new Environment(new ArrayLoader()));

        // Defense in depth: a single quote in the source value must NOT appear as a
        // literal "'" in the compiled output, so that even if a caller mistakenly
        // concatenates the result into a single-quoted PHP string, the value cannot
        // break out of that context. It must still decode back to the original byte.
        $source = $compiler->string("it's \"a\" test")->getSource();

        $this->assertStringNotContainsString("'", $source);
        $this->assertSame('"it\\x27s \\"a\\" test"', $source);

        $decoded = null;
        eval('$decoded = '.$source.';');
        $this->assertSame("it's \"a\" test", $decoded);
    }

    public function testReprNumericValueWithLocale()
    {
        $compiler = new Compiler(new Environment(new ArrayLoader()));

        $locale = setlocale(\LC_NUMERIC, '0');
        if (false === $locale) {
            $this->markTestSkipped('Your platform does not support locales.');
        }

        $required_locales = ['fr_FR.UTF-8', 'fr_FR.UTF8', 'fr_FR.utf-8', 'fr_FR.utf8', 'French_France.1252'];
        if (false === setlocale(\LC_NUMERIC, $required_locales)) {
            $this->markTestSkipped('Could not set any of required locales: '.implode(', ', $required_locales));
        }

        $this->assertEquals('1.2', $compiler->repr(1.2)->getSource());
        $this->assertStringContainsString('fr', strtolower(setlocale(\LC_NUMERIC, '0')));

        setlocale(\LC_NUMERIC, $locale);
    }
}
