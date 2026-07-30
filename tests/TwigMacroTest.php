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
    public function testLegacyCallEnrichesErrorsWithTheCallSiteSourceAndLine(): void
    {
        $macro = new TwigMacro('input', static fn () => '', ['name' => false]);

        try {
            $macro->callLegacy(['name' => 'a', 0 => 'b'], new Source('', 'index.twig'), 7);

            $this->fail('Expected a RuntimeError to be thrown.');
        } catch (RuntimeError $e) {
            $this->assertSame('Positional arguments cannot be used after named arguments for macro "input" in "index.twig" at line 7.', $e->getMessage());
        }
    }

    public function testLegacyCallDoesNotReportDeprecationsForACallThatThrows(): void
    {
        $macro = new TwigMacro('input', static fn () => '', ['name' => false]);

        $deprecations = $this->collectDeprecations(function () use ($macro) {
            try {
                $macro->callLegacy(['unknown' => 'a', 0 => 'b'], new Source('', 'index.twig'), 7);

                $this->fail('Expected a RuntimeError to be thrown.');
            } catch (RuntimeError $e) {
                $this->assertSame('Positional arguments cannot be used after named arguments for macro "input" in "index.twig" at line 7.', $e->getMessage());
            }
        });

        $this->assertSame([], $deprecations);
    }

    public function testLegacyCallRejectsAnArgumentDefinedTwice(): void
    {
        $macro = new TwigMacro('input', static fn () => '', ['name' => false]);

        try {
            $macro->callLegacy([0 => 'a', 'name' => 'b'], new Source('', 'index.twig'), 7);

            $this->fail('Expected a RuntimeError to be thrown.');
        } catch (RuntimeError $e) {
            $this->assertSame('Argument "name" is defined twice for macro "input" in "index.twig" at line 7.', $e->getMessage());
        }
    }

    /**
     * The body closures mirror what MacroNode compiles on 3.x: every declared
     * argument becomes a defaulted parameter (reserved names prefixed) and a
     * trailing variadic bucket collects the extra arguments.
     *
     * @dataProvider provideLegacyCalls
     */
    #[DataProvider('provideLegacyCalls')]
    public function testLegacyCallIsLenientButReportsFutureErrors(array $signature, bool $variadic, \Closure $bodyFactory, array $arguments, array $expectedArguments, array $expectedDeprecations): void
    {
        $captured = null;
        $macro = new TwigMacro('test', $bodyFactory($captured), $signature, $variadic);

        $deprecations = $this->collectDeprecations(static function () use ($macro, $arguments) {
            $macro->callLegacy($arguments, new Source('', 'index.twig'), 7);
        });

        $this->assertSame($expectedArguments, $captured);
        $this->assertSame($expectedDeprecations, $deprecations);
    }

    public static function provideLegacyCalls(): iterable
    {
        $nameOnly = static function (&$captured) {
            return static function ($name = null, ...$varargs) use (&$captured) {
                $captured = [$name, $varargs];

                return '';
            };
        };

        $nameValueType = static function (&$captured) {
            return static function ($name = null, $value = 'v', $type = 't', ...$varargs) use (&$captured) {
                $captured = [$name, $value, $type, $varargs];

                return '';
            };
        };

        yield 'missing argument without a default is lenient and deprecated' => [
            ['name' => false, 'value' => true],
            false,
            static function (&$captured) {
                return static function ($name = null, $value = null, ...$varargs) use (&$captured) {
                    $captured = [$name, $value, $varargs];

                    return '';
                };
            },
            [],
            [null, null, []],
            ['Since twig/twig 3.29: Not passing a value for the "name" argument of macro "test" is deprecated and the argument will be required in Twig 4.0; give it a default value in the macro definition or pass a value when calling it (in "index.twig" at line 7).'],
        ];

        yield 'extra positional argument is lenient and deprecated' => [
            ['name' => false],
            false,
            $nameOnly,
            [0 => 'a', 1 => 'b'],
            ['a', ['b']],
            ['Since twig/twig 3.29: Passing more arguments than the macro "test" accepts is deprecated and will throw in Twig 4.0; declare a variadic argument ("...name") in the macro definition to accept extra arguments (in "index.twig" at line 7).'],
        ];

        yield 'unknown named argument is lenient and deprecated' => [
            ['name' => false],
            false,
            $nameOnly,
            ['name' => 'a', 'extra' => 'b'],
            ['a', ['extra' => 'b']],
            ['Since twig/twig 3.29: Passing the unknown named argument "extra" to the macro "test" is deprecated and will throw in Twig 4.0; declare a variadic argument ("...name") in the macro definition to accept it (in "index.twig" at line 7).'],
        ];

        yield 'null named argument value satisfies a required argument' => [
            ['name' => false],
            false,
            $nameOnly,
            ['name' => null],
            [null, []],
            [],
        ];

        yield 'a reserved argument name maps onto its prefixed parameter' => [
            ['name' => true, 'blocks' => true],
            false,
            static function (&$captured) {
                return static function ($name = null, $͜blocks = 'default', ...$varargs) use (&$captured) {
                    $captured = [$name, $͜blocks, $varargs];

                    return '';
                };
            },
            ['blocks' => 'value'],
            [null, 'value', []],
            [],
        ];

        yield 'skipped optional arguments before a named argument get their defaults' => [
            ['name' => false, 'value' => true, 'type' => true],
            false,
            $nameValueType,
            ['name' => 'a', 'type' => 'x'],
            ['a', 'v', 'x', []],
            [],
        ];

        yield 'positional and named arguments mix under the legacy call path' => [
            ['name' => false, 'value' => true, 'type' => true],
            false,
            $nameValueType,
            [0 => 'a', 'type' => 'x'],
            ['a', 'v', 'x', []],
            [],
        ];

        yield 'known named arguments bind to their parameter and unknown ones fall into the variadic bucket' => [
            ['name' => false, 'value' => true, 'type' => true],
            true,
            $nameValueType,
            [0 => 'a', 'type' => 'submit', 'extra' => 'value'],
            ['a', 'v', 'submit', ['extra' => 'value']],
            [],
        ];

        yield 'a variadic macro reports nothing' => [
            ['name' => false],
            true,
            $nameOnly,
            [0 => 'a', 1 => 'b', 'extra' => 'c'],
            ['a', ['b', 'extra' => 'c']],
            [],
        ];
    }

    private function collectDeprecations(callable $fn): array
    {
        $deprecations = [];
        set_error_handler(static function ($type, $message) use (&$deprecations) {
            if (\E_USER_DEPRECATED === $type) {
                $deprecations[] = $message;

                return true;
            }

            return false;
        });

        try {
            $fn();
        } finally {
            restore_error_handler();
        }

        return $deprecations;
    }
}
