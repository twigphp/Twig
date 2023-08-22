<?php

namespace Twig\Tests\Extension;

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use PHPUnit\Framework\TestCase;
use Twig\Extension\EnumExtension;

/**
 * @requires PHP 8.1
 */
class EnumExtensionTest extends TestCase
{
    public function testEnumCases()
    {
        $cases = EnumExtension::enumCases(MyBackedEnum::class);

        $this->assertSame(MyBackedEnum::cases(), $cases);
    }

    public function testEnumCasesThrowsIfNotBacked()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The enum must be a "\BackedEnum", "Twig\Tests\Extension\MyUnitEnum" given.');

        EnumExtension::enumCases(MyUnitEnum::class);
    }
}

if (80100 <= \PHP_VERSION_ID) {
    enum MyBackedEnum: string
    {
        case ONE = 'one';
        case TWO = 'two';
    }

    enum MyUnitEnum
    {
        case ONE;
        case TWO;
    }
}
