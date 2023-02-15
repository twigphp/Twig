<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Test;

/**
 * Integration test helper.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Karma Dordrak <drak@zikula.org>
 */
abstract class TemplateIntegrationTestCase extends BaseTemplateIntegrationTestCase
{
    /**
     * Returns a path to a directory containing all fixtures (i.e. *.test files)
     * Tests can be put into more directories under this one.
     */
    abstract public static function getFixturesDir(): string;

    public static function provideTests()
    {
        return static::provideTestsImpl(false, static::getFixturesDir());
    }

    public static function provideLegacyTests()
    {
        return static::provideTestsImpl(true, static::getFixturesDir());
    }

    /**
     * @dataProvider provideTests
     */
    public function testIntegration($file, $message, $condition, $templates, $exception, $outputs, $deprecation = '')
    {
        $this->doIntegrationTest($file, $message, $condition, $templates, $exception, $outputs, $deprecation);
    }

    /**
     * @dataProvider provideLegacyTests
     *
     * @group legacy
     */
    public function testLegacyIntegration($file, $message, $condition, $templates, $exception, $outputs, $deprecation = '')
    {
        $this->doIntegrationTest($file, $message, $condition, $templates, $exception, $outputs, $deprecation);
    }
}
