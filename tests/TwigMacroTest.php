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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\TwigMacro;

class TwigMacroTest extends TestCase
{
    #[DataProvider('provideInvalidCalls')]
    public function testCallRejectsInvalidArguments(array $signature, bool $variadic, array $arguments, string $message): void
    {
        $macro = new TwigMacro('input', static fn () => '', $signature, $variadic);

        $this->expectException(RuntimeError::class);
        $this->expectExceptionMessage($message.' in "index.twig" at line 7.');

        $macro->call($arguments, new Source('', 'index.twig'), 7);
    }

    public static function provideInvalidCalls(): iterable
    {
        yield 'positional argument after a named argument' => [
            ['name' => false],
            false,
            ['name' => 'a', 0 => 'b'],
            'Positional arguments cannot be used after named arguments for macro "input"',
        ];

        yield 'argument defined twice' => [
            ['name' => false],
            false,
            [0 => 'a', 'name' => 'b'],
            'Argument "name" is defined twice for macro "input"',
        ];

        yield 'missing required argument' => [
            ['name' => false, 'value' => true],
            false,
            [],
            'Value for argument "name" is required for macro "input"',
        ];

        yield 'extra positional argument' => [
            ['name' => false],
            false,
            ['a', 'b'],
            'Too many arguments for macro "input"',
        ];

        yield 'unknown named argument' => [
            ['name' => false],
            false,
            ['name' => 'a', 'extra' => 'b'],
            'Unknown argument "extra" for macro "input"',
        ];
    }

    #[DataProvider('provideValidCalls')]
    public function testCallBindsArguments(array $signature, bool $variadic, \Closure $bodyFactory, array $arguments, array $expectedArguments): void
    {
        $captured = null;
        $macro = new TwigMacro('test', $bodyFactory($captured), $signature, $variadic);

        $macro->call($arguments, new Source('', 'index.twig'), 7);

        $this->assertSame($expectedArguments, $captured);
    }

    public static function provideValidCalls(): iterable
    {
        $nameOnly = static function (&$captured) {
            return static function ($name = null) use (&$captured) {
                $captured = [$name];

                return '';
            };
        };

        $nameValueType = static function (&$captured) {
            return static function ($name = null, $value = 'v', $type = 't') use (&$captured) {
                $captured = [$name, $value, $type];

                return '';
            };
        };

        yield 'null satisfies a required argument' => [
            ['name' => false],
            false,
            $nameOnly,
            ['name' => null],
            [null],
        ];

        yield 'a reserved argument name maps onto its prefixed parameter' => [
            ['name' => true, 'blocks' => true],
            false,
            static function (&$captured) {
                return static function ($name = null, $͜blocks = 'default') use (&$captured) {
                    $captured = [$name, $͜blocks];

                    return '';
                };
            },
            ['blocks' => 'value'],
            [null, 'value'],
        ];

        yield 'skipped optional arguments before a named argument get their defaults' => [
            ['name' => false, 'value' => true, 'type' => true],
            false,
            $nameValueType,
            ['name' => 'a', 'type' => 'x'],
            ['a', 'v', 'x'],
        ];

        yield 'positional and named arguments can be mixed' => [
            ['name' => false, 'value' => true, 'type' => true],
            false,
            $nameValueType,
            [0 => 'a', 'type' => 'x'],
            ['a', 'v', 'x'],
        ];

        yield 'a variadic macro collects positional and named arguments' => [
            ['name' => false],
            true,
            static function (&$captured) {
                return static function ($name = null, ...$varargs) use (&$captured) {
                    $captured = [$name, $varargs];

                    return '';
                };
            },
            [0 => 'a', 1 => 'b', 'extra' => 'c'],
            ['a', ['b', 'extra' => 'c']],
        ];
    }
}
