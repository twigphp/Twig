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
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Loader\FilesystemLoader;
use Twig\Util\TemplateBacktraceMapper;

class TemplateBacktraceMapperTest extends TestCase
{
    public function testMapsACompiledTemplateFrameToItsSource(): void
    {
        $twig = new Environment(new FilesystemLoader(__DIR__.'/Fixtures'), ['debug' => true]);
        $template = $twig->load('backtrace_mapper.html.twig')->unwrap($twig);
        $debugInfo = $template->getDebugInfo();
        $compiledLine = array_key_first($debugInfo);
        $compiledFile = (new \ReflectionObject($template))->getFileName();

        $mapped = (new TemplateBacktraceMapper())->map([
            ['class' => 'Foo', 'function' => 'count', 'file' => $compiledFile, 'line' => $compiledLine],
            ['class' => $template::class, 'function' => 'doDisplay', 'file' => '/vendor/twig/twig/src/Template.php', 'line' => 388],
            ['file' => '/app/src/Controller.php', 'line' => 20, 'function' => 'index'],
        ]);

        $this->assertSame([
            ['class' => 'Foo', 'function' => 'count', 'file' => __DIR__.'/Fixtures/backtrace_mapper.html.twig', 'line' => $debugInfo[$compiledLine]],
            ['class' => 'backtrace_mapper.html.twig', 'function' => 'doDisplay', 'file' => '/vendor/twig/twig/src/Template.php', 'line' => 388, 'type' => '%'],
            ['file' => '/app/src/Controller.php', 'line' => 20, 'function' => 'index'],
        ], $mapped);
    }

    public function testLeavesATraceWithoutTemplateAlone(): void
    {
        $trace = [['file' => '/app/src/Controller.php', 'line' => 20, 'function' => 'index']];

        $this->assertSame($trace, (new TemplateBacktraceMapper())->map($trace));
    }

    public function testMapsABacktraceCollectedInATemplate(): void
    {
        $twig = new Environment(new FilesystemLoader(__DIR__.'/Fixtures'), ['debug' => true]);
        $twig->addFunction(new \Twig\TwigFunction('trace', static fn () => (new TemplateBacktraceMapper())->map(debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS))));
        $twig->setLoader(new ArrayLoader(['t.twig' => "a\n{% set t = trace() %}{{ t|filter(f => f.file == 't.twig')|length }}"]));

        $this->assertSame("a\n1", $twig->render('t.twig'));
    }

    public function testMapsTheRightTemplateWhenSeveralTemplatesShareTheSameFile(): void
    {
        $twig = new Environment(new ArrayLoader(['a.twig' => '{{ a }}', 'b.twig' => '{{ b }}']), ['debug' => true, 'cache' => false]);
        $a = $twig->load('a.twig')->unwrap($twig);
        $b = $twig->load('b.twig')->unwrap($twig);
        $file = (new \ReflectionObject($a))->getFileName();
        $this->assertSame($file, (new \ReflectionObject($b))->getFileName());

        $trace = [
            ['file' => $file, 'line' => array_key_first($b->getDebugInfo()), 'function' => 'doDisplay'],
            ['class' => $b::class, 'function' => 'doDisplay'],
        ];

        $this->assertSame('b.twig', (new TemplateBacktraceMapper())->map($trace)[0]['file']);
    }

    public function testUsesTheTemplateObjectWhenProvided(): void
    {
        $twig = new Environment(new FilesystemLoader(__DIR__.'/Fixtures'), ['debug' => true]);
        $template = $twig->load('backtrace_mapper.html.twig')->unwrap($twig);
        $debugInfo = $template->getDebugInfo();
        $compiledLine = array_key_first($debugInfo);
        $compiledFile = (new \ReflectionObject($template))->getFileName();

        // The class name is not a template class, so only the object can be used.
        $mapped = (new TemplateBacktraceMapper())->map([
            ['function' => 'count', 'file' => $compiledFile, 'line' => $compiledLine],
            ['class' => 'NotATemplate', 'function' => 'doDisplay', 'object' => $template],
        ]);

        $this->assertSame([
            ['function' => 'count', 'file' => __DIR__.'/Fixtures/backtrace_mapper.html.twig', 'line' => $debugInfo[$compiledLine]],
            ['class' => 'NotATemplate', 'function' => 'doDisplay'],
        ], $mapped);
    }

    public function testMapsABacktraceCollectedWithObjects(): void
    {
        $twig = new Environment(new ArrayLoader(['t.twig' => "a\n{% set t = trace() %}{{ t|filter(f => f.file == 't.twig')|length }}"]), ['debug' => true]);
        $twig->addFunction(new \Twig\TwigFunction('trace', static fn () => (new TemplateBacktraceMapper())->map(debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS | \DEBUG_BACKTRACE_PROVIDE_OBJECT))));

        $this->assertSame("a\n1", $twig->render('t.twig'));
    }

    public function testRenamesTheFramesOfATemplate(): void
    {
        $twig = new Environment(new FilesystemLoader(__DIR__.'/Fixtures'), ['debug' => true]);
        $template = $twig->load('backtrace_mapper.html.twig')->unwrap($twig);
        $class = $template::class;

        $mapped = (new TemplateBacktraceMapper())->map([
            ['class' => $class, 'function' => 'block_main', 'type' => '->'],
            ['class' => $class, 'function' => "{closure:{$class}::loadDeclaredMacros():49}", 'type' => '->'],
            ['class' => 'Twig\\Template', 'function' => 'yield', 'type' => '->', 'object' => $template],
        ]);

        $this->assertSame([
            ['class' => 'backtrace_mapper.html.twig', 'function' => 'block main', 'type' => '%'],
            ['class' => 'backtrace_mapper.html.twig', 'function' => '{closure:backtrace_mapper.html.twig::loadDeclaredMacros():49}', 'type' => '%'],
            ['class' => 'Twig\\Template', 'function' => 'yield', 'type' => '->'],
        ], $mapped);
    }
}
