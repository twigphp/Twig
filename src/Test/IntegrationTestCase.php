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

use PHPUnit\Framework\TestCase;
use Twig\Test\Internal\IntegrationTestTrait;

/**
 * Integration test helper.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Karma Dordrak <drak@zikula.org>
 *
 * @deprecated use \Twig\Test\TemplateIntegrationTestCase instead
 */
abstract class IntegrationTestCase extends TestCase
{
    use IntegrationTestTrait;

    /**
     * @return string
     */
    abstract protected function getFixturesDir();

    /**
     * @dataProvider getTests
     */
    public function testIntegration($file, $message, $condition, $templates, $exception, $outputs, $deprecation = '')
    {
        $this->doIntegrationTest($file, $message, $condition, $templates, $exception, $outputs, $deprecation);
    }

    /**
     * @dataProvider getLegacyTests
     *
     * @group legacy
     */
    public function testLegacyIntegration($file, $message, $condition, $templates, $exception, $outputs, $deprecation = '')
    {
        $this->doIntegrationTest($file, $message, $condition, $templates, $exception, $outputs, $deprecation);
    }

    public function getTests($name, $legacyTests = false)
    {
        return static::provideTestsImpl($legacyTests, $this->getFixturesDir());
    }

    public function getLegacyTests()
    {
        return static::provideTestsImpl(true, $this->getFixturesDir());
    }
}
