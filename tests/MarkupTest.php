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
use Twig\Markup;

class MarkupTest extends TestCase
{
    public function testContentIsSafeForAllStrategiesByDefault(): void
    {
        $this->assertTrue((new Markup('<br />', 'UTF-8'))->isSafeFor('js'));
    }

    /**
     * @dataProvider provideSafeStrategies
     */
    #[DataProvider('provideSafeStrategies')]
    public function testContentIsSafeForTheGivenStrategies(bool $safe, array $safeStrategies, string $strategy): void
    {
        $this->assertSame($safe, (new Markup('<br />', 'UTF-8', $safeStrategies))->isSafeFor($strategy));
    }

    public static function provideSafeStrategies(): iterable
    {
        yield [true, ['all'], 'js'];
        yield [true, ['html', 'js'], 'js'];
        yield [false, ['html', 'js'], 'css'];
        yield [true, ['html_attr'], 'html'];
        yield [true, ['html_attr'], 'html_attr_relaxed'];
        yield [false, ['html'], 'html_attr'];
        yield [false, [], 'html'];
    }

    public function testWithContentKeepsTheSafeStrategies(): void
    {
        $markup = (new Markup('<br />', 'UTF-8', ['html']))->withContent('<hr />');

        $this->assertSame('<hr />', (string) $markup);
        $this->assertTrue($markup->isSafeFor('html'));
        $this->assertFalse($markup->isSafeFor('js'));
    }
}
