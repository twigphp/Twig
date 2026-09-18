<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Tests\Runtime;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Twig\Markup;
use Twig\Runtime\EscaperRuntime;

/**
 * @group legacy
 */
#[Group('legacy')]
class EscaperRuntimeLegacyTest extends TestCase
{
    use ExpectDeprecationTrait;

    public function testContentProducedForAnotherStrategyIsDeprecatedInsteadOfEscaped(): void
    {
        $markup = Markup::createForStrategy('<br />', 'UTF-8', 'html');

        $this->expectDeprecation('Since twig/twig 3.31: Printing content produced with the "html" escaping strategy in a "js" context is deprecated; it will be escaped in 4.0.');

        $this->assertSame($markup, (new EscaperRuntime())->escape($markup, 'js', null, true));
    }

    public function testContentProducedForAStricterStrategyIsDeprecatedToo(): void
    {
        $markup = Markup::createForStrategy('<br />', 'UTF-8', 'js');

        $this->expectDeprecation('Since twig/twig 3.31: Printing content produced with the "js" escaping strategy in a "html" context is deprecated; it will be escaped in 4.0.');

        $this->assertSame($markup, (new EscaperRuntime())->escape($markup, 'html', null, true));
    }

    public function testContentProducedForAnUnknownStrategyIsNotDeprecated(): void
    {
        $markup = Markup::createForStrategy('<br />', 'UTF-8', 'html');

        $this->assertSame($markup, (new EscaperRuntime())->escape($markup, '1', null, true));
    }
}
