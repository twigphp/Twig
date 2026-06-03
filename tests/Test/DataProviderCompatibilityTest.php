<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Tests\Test;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Twig\Test\IntegrationTestCase;
use Twig\Test\NodeTestCase;

/**
 * Guards that the shipped test case classes stay usable on PHPUnit >= 11,
 * which rejects non-static data providers.
 */
final class DataProviderCompatibilityTest extends TestCase
{
    /**
     * @return iterable<array{class-string}>
     */
    public static function provideTestCaseClasses(): iterable
    {
        yield [IntegrationTestCase::class];
        yield [NodeTestCase::class];
    }

    /**
     * @param class-string $class
     */
    #[DataProvider('provideTestCaseClasses')]
    public function testDataProviderAttributesReferenceStaticMethods(string $class): void
    {
        $found = false;
        foreach ((new \ReflectionClass($class))->getMethods() as $method) {
            foreach ($method->getAttributes(DataProvider::class) as $attribute) {
                $found = true;
                $provider = $attribute->getArguments()[0];
                $r = new \ReflectionMethod($class, $provider);
                $this->assertTrue($r->isStatic(), \sprintf('Data provider "%s::%s()" referenced by "%s()" must be static for PHPUnit >= 11.', $class, $provider, $method->getName()));
                $this->assertSame(0, $r->getNumberOfRequiredParameters(), \sprintf('Data provider "%s::%s()" must not require arguments.', $class, $provider));
            }
        }

        $this->assertTrue($found, \sprintf('"%s" should declare at least one "#[DataProvider]" attribute.', $class));
    }
}
