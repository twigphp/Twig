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

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Twig\Test\IntegrationTestCase;

final class IntegrationTestCaseTest extends TestCase
{
    public function testLegacyIntegrationTestsAreInLegacyGroup(): void
    {
        $attributes = (new \ReflectionMethod(IntegrationTestCase::class, 'testLegacyIntegration'))->getAttributes(Group::class);

        $this->assertCount(1, $attributes);
        $this->assertSame('legacy', $attributes[0]->newInstance()->name());
    }
}
