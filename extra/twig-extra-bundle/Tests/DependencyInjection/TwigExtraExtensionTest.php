<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Extra\TwigExtraBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Twig\Extra\Markdown\LeagueMarkdown;
use Twig\Extra\TwigExtraBundle\DependencyInjection\TwigExtraExtension;
use Twig\Extra\TwigExtraBundle\Extensions;

class TwigExtraExtensionTest extends TestCase
{
    public function testDefaultConfiguration()
    {
        $container = new ContainerBuilder(new ParameterBag([
            'kernel.debug' => false,
        ]));
        $container->registerExtension(new TwigExtraExtension());
        $container->loadFromExtension('twig_extra');
        $container->getCompilerPassConfig()->setOptimizationPasses([]);
        $container->getCompilerPassConfig()->setRemovingPasses([]);
        $container->getCompilerPassConfig()->setAfterRemovingPasses([]);
        $container->compile();

        foreach (Extensions::getClasses() as $name => $class) {
            $this->assertEquals($class, $container->getDefinition('twig.extension.'.$name)->getClass());
        }

        $this->assertSame(LeagueMarkdown::class, $container->getDefinition('twig.markdown.default')->getClass());

        $commonmarkConverterFactory = $container->getDefinition('twig.markdown.league_common_mark_converter')->getFactory();

        $this->assertSame('twig.markdown.league_common_mark_converter_factory', (string) $commonmarkConverterFactory[0]);
        $this->assertSame('__invoke', $commonmarkConverterFactory[1]);
    }
}
