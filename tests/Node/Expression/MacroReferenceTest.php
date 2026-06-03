<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Tests\Node\Expression;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Expression\MacroReferenceExpression;
use Twig\Node\Expression\Variable\TemplateVariable;

class MacroReferenceTest extends TestCase
{
    /**
     * @dataProvider provideInvalidMacroNames
     */
    #[DataProvider('provideInvalidMacroNames')]
    public function testConstructorRejectsNonIdentifierName(string $name)
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(\sprintf('Macro name "%s" is not a valid PHP identifier.', $name));

        new MacroReferenceExpression(new TemplateVariable('foo', 1), $name, new ArrayExpression([], 1), 1);
    }

    public static function provideInvalidMacroNames(): iterable
    {
        yield 'empty' => [''];
        yield 'starts with digit' => ['1foo'];
        yield 'contains space' => ['foo bar'];
        yield 'contains semicolon' => ['foo;bar'];
        yield 'PHP injection payload' => ['macro_foo + 1; trigger_error("BAD") //'];
        yield 'contains NUL byte' => ["foo\x00bar"];
    }
}
