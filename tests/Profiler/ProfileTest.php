<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Tests\Profiler;

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use PHPUnit\Framework\TestCase;
use Twig\Profiler\Profile;

class ProfileTest extends TestCase
{
    public function testConstructor(): void
    {
        $profile = new Profile('template', 'type', 'name');

        $this->assertEquals('template', $profile->getTemplate());
        $this->assertEquals('type', $profile->getType());
        $this->assertEquals('name', $profile->getName());
    }

    public function testIsRoot(): void
    {
        $profile = new Profile('template', Profile::ROOT);
        $this->assertTrue($profile->isRoot());

        $profile = new Profile('template', Profile::TEMPLATE);
        $this->assertFalse($profile->isRoot());
    }

    public function testIsTemplate(): void
    {
        $profile = new Profile('template', Profile::TEMPLATE);
        $this->assertTrue($profile->isTemplate());

        $profile = new Profile('template', Profile::ROOT);
        $this->assertFalse($profile->isTemplate());
    }

    public function testIsBlock(): void
    {
        $profile = new Profile('template', Profile::BLOCK);
        $this->assertTrue($profile->isBlock());

        $profile = new Profile('template', Profile::ROOT);
        $this->assertFalse($profile->isBlock());
    }

    public function testIsMacro(): void
    {
        $profile = new Profile('template', Profile::MACRO);
        $this->assertTrue($profile->isMacro());

        $profile = new Profile('template', Profile::ROOT);
        $this->assertFalse($profile->isMacro());
    }

    public function testGetAddProfile(): void
    {
        $profile = new Profile();
        $profile->addProfile($a = new Profile());
        $profile->addProfile($b = new Profile());

        $this->assertSame([$a, $b], $profile->getProfiles());
        $this->assertSame([$a, $b], iterator_to_array($profile));
    }

    public function testGetDuration(): void
    {
        $profile = new Profile();
        usleep(1);
        $profile->leave();

        $this->assertTrue($profile->getDuration() > 0, \sprintf('Expected duration > 0, got: %f', $profile->getDuration()));
    }

    public function testTimeAccessors(): void
    {
        $current = microtime(true);
        $profile = new Profile();

        $this->assertEqualsWithDelta($current, $profile->getStartTime(), 1);
        $this->assertSame(0.0, $profile->getEndTime());
    }

    public function testSerialize(): void
    {
        $profile = new Profile('template', 'type', 'name');
        $profile1 = new Profile('template1', 'type1', 'name1');
        $profile->addProfile($profile1);
        $profile->leave();
        $profile1->leave();

        $profile2 = unserialize(serialize($profile));
        $profiles = $profile->getProfiles();
        $this->assertCount(1, $profiles);
        $profile3 = $profiles[0];

        $this->assertEquals($profile->getTemplate(), $profile2->getTemplate());
        $this->assertEquals($profile->getType(), $profile2->getType());
        $this->assertEquals($profile->getName(), $profile2->getName());
        $this->assertEquals($profile->getDuration(), $profile2->getDuration());

        $this->assertEquals($profile1->getTemplate(), $profile3->getTemplate());
        $this->assertEquals($profile1->getType(), $profile3->getType());
        $this->assertEquals($profile1->getName(), $profile3->getName());
    }

    public function testUnserializeDoesNotInstantiateArbitraryClasses(): void
    {
        $payload = serialize([
            'template',
            'name',
            Profile::ROOT,
            [],
            [],
            [new ProfileTestProbe()],
        ]);

        $profile = new Profile();
        $profile->unserialize($payload);

        $this->assertFalse(ProfileTestProbe::$wakeupCalled, 'Magic unserialize methods must not be called on arbitrary classes');
    }

    public function testReset(): void
    {
        $profile = new Profile();
        usleep(1);
        $profile->leave();
        $profile->reset();

        $this->assertEquals(0, $profile->getDuration());
    }
}

class ProfileTestProbe
{
    public static bool $wakeupCalled = false;

    public function __unserialize(array $data): void
    {
        self::$wakeupCalled = true;
    }
}
