<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Tests\Util;

use PHPUnit\Framework\TestCase;
use Twig\Util\CallableParameters;

class CallableParametersTest extends TestCase
{
    /**
     * @dataProvider provideTypes
     */
    public function testIsStringCoercionSafe(?\ReflectionType $type, bool $expected, ?\ReflectionClass $scope = null): void
    {
        $this->assertSame($expected, CallableParameters::isStringCoercionSafe($type, $scope));
    }

    public function testIsStringCoercionSafeDoesNotAutoloadUnknownClasses(): void
    {
        $autoloaded = false;
        $autoload = static function () use (&$autoloaded): void {
            $autoloaded = true;
        };
        spl_autoload_register($autoload);

        try {
            $type = (new \ReflectionFunction(eval('return static fn (CallableParametersAutoloadProbe $x) => null;')))->getParameters()[0]->getType();

            $this->assertFalse(CallableParameters::isStringCoercionSafe($type));
            $this->assertFalse($autoloaded);
        } finally {
            spl_autoload_unregister($autoload);
        }
    }

    public static function provideTypes(): iterable
    {
        $param = static fn (\Closure $c): ?\ReflectionType => (new \ReflectionFunction($c))->getParameters()[0]->getType();
        $mParam = static fn (string $class, string $m): ?\ReflectionType => (new \ReflectionMethod($class, $m))->getParameters()[0]->getType();
        $mReturn = static fn (string $class, string $m): ?\ReflectionType => (new \ReflectionMethod($class, $m))->getReturnType();

        // No type information: must keep the check.
        yield 'untyped' => [$param(static fn ($x) => null), false];

        // Builtin scalars that cannot hold a string-coercible value.
        yield 'int' => [$param(static fn (int $x) => null), true];
        yield 'float' => [$param(static fn (float $x) => null), true];
        yield 'bool' => [$param(static fn (bool $x) => null), true];
        yield 'never (return)' => [(new \ReflectionFunction(static function (): never { throw new \LogicException(); }))->getReturnType(), true];

        // Builtins that can carry or coerce to a string.
        yield 'string' => [$param(static fn (string $x) => null), false];
        yield 'array' => [$param(static fn (array $x) => null), false];
        yield 'iterable' => [$param(static fn (iterable $x) => null), false];
        yield 'object' => [$param(static fn (object $x) => null), false];
        yield 'mixed' => [$param(static fn (mixed $x) => null), false];
        yield 'callable' => [$param(static fn (callable $x) => null), false];

        // Class types: safe only when final and neither Stringable nor
        // Traversable. Interfaces and non-final classes are open (a subtype
        // could add Stringable/Traversable), so they must stay unsafe.
        yield 'final plain class' => [$param(static fn (FinalPlainObject $x) => null), true];
        yield 'nullable final class' => [$param(static fn (?FinalPlainObject $x) => null), true];
        yield 'non-final class' => [$param(static fn (\stdClass $x) => null), false];
        yield 'final Stringable class' => [$param(static fn (FinalStringableObject $x) => null), false];
        yield 'Countable interface' => [$param(static fn (\Countable $x) => null), false];
        yield 'Stringable interface' => [$param(static fn (\Stringable $x) => null), false];
        yield 'Traversable interface' => [$param(static fn (\Iterator $x) => null), false];
        yield 'Traversable class' => [$param(static fn (\ArrayIterator $x) => null), false];

        // Enums are implicitly final and cannot be Stringable (no __toString).
        yield 'backed enum' => [$param(static fn (SampleBackedEnum $x) => null), true];
        yield 'pure enum' => [$param(static fn (SamplePureEnum $x) => null), true];

        // `self`/`parent`/`static` resolve to the declaring class (passed as
        // scope) and are then judged by the class rules. PHP < 8.4 reports
        // them verbatim, recent versions resolve them in getName(); both paths
        // must yield the same verdict.
        yield 'self -> Stringable class' => [$mParam(CoercionFixture::class, 'selfParam'), false, new \ReflectionClass(CoercionFixture::class)];
        yield 'self -> final class' => [$mParam(FinalSelfFixture::class, 'selfParam'), true, new \ReflectionClass(FinalSelfFixture::class)];
        yield 'parent -> non-final class' => [$mParam(CoercionFixture::class, 'parentParam'), false, new \ReflectionClass(CoercionFixture::class)];
        yield 'static (return) -> self' => [$mReturn(CoercionFixture::class, 'staticReturn'), false, new \ReflectionClass(CoercionFixture::class)];

        // Union: the value is one of the members, so all must be safe.
        yield 'int|float union' => [$param(static fn (int|float $x) => null), true];
        yield 'int|null union' => [$param(static fn (?int $x) => null), true];
        yield 'int|float|null union' => [$param(static fn (int|float|null $x) => null), true];
        yield 'int|string union' => [$param(static fn (int|string $x) => null), false];

        // Intersection: safe as soon as one member is a final, non-coercible
        // class (it pins the concrete class); otherwise open and unsafe.
        yield 'final class intersection' => [$param(static fn (FinalMarkedObject&SampleMarker $x) => null), true];
        yield 'interface intersection' => [$param(static fn (\Countable&\ArrayAccess $x) => null), false];
        yield 'Stringable intersection' => [$param(static fn (\Stringable&\Countable $x) => null), false];
        yield 'Traversable intersection' => [$param(static fn (\Traversable&\Countable $x) => null), false];
    }
}

enum SampleBackedEnum: string
{
    case A = 'a';
}

enum SamplePureEnum
{
    case A;
}

interface SampleMarker
{
}

final class FinalMarkedObject implements SampleMarker
{
}

final class FinalPlainObject
{
}

final class FinalStringableObject implements \Stringable
{
    public function __toString(): string
    {
        return '';
    }
}

final class FinalSelfFixture
{
    public function selfParam(self $x): void
    {
    }
}

class CoercionFixtureParent
{
}

class CoercionFixture extends CoercionFixtureParent implements \Stringable
{
    public function selfParam(self $x): void
    {
    }

    public function parentParam(parent $x): void
    {
    }

    public function staticReturn(): static
    {
        return $this;
    }

    public function __toString(): string
    {
        return '';
    }
}
