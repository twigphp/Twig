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
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\Extension\EscaperExtension;
use Twig\Extension\SandboxExtension;
use Twig\Loader\ArrayLoader;
use Twig\Node\Node;
use Twig\Node\TextNode;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedTestError;
use Twig\Sandbox\SecurityPolicy;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

class EnvironmentOptionsHashTest extends TestCase
{
    public function testResolvedAutoescapeStrategyIsPartOfTheTemplateClass(): void
    {
        $cache = $this->createCacheDirectory();
        $loader = new ArrayLoader(['index' => '{{ value }}']);

        try {
            $escaped = new Environment($loader, ['cache' => $cache, 'autoescape' => EnvironmentOptionsHashCallables::htmlStrategy(...)]);
            $unescaped = new Environment($loader, ['cache' => $cache, 'autoescape' => EnvironmentOptionsHashCallables::noAutoescapeStrategy(...)]);

            $this->assertNotSame($escaped->getTemplateClass('index'), $unescaped->getTemplateClass('index'));
            $this->assertSame('&lt;strong&gt;', $escaped->render('index', ['value' => '<strong>']));
            $this->assertSame('<strong>', $unescaped->render('index', ['value' => '<strong>']));
        } finally {
            FilesystemHelper::removeDir($cache);
        }
    }

    /**
     * @dataProvider provideStagedSandboxRegistrations
     */
    #[DataProvider('provideStagedSandboxRegistrations')]
    public function testStagedSandboxRegistrationIsPartOfTheTemplateClass(string $template, string $expected, \Closure $register, string $exception): void
    {
        $cache = $this->createCacheDirectory();
        $loader = new ArrayLoader(['index' => $template]);

        try {
            $allowed = $this->createSandboxedEnvironment($loader, $cache);
            $register($allowed, true);
            $denied = $this->createSandboxedEnvironment($loader, $cache);
            $register($denied, false);

            $this->assertNotSame($allowed->getTemplateClass('index'), $denied->getTemplateClass('index'));
            $this->assertSame($expected, $allowed->render('index'));

            $this->expectException($exception);
            $denied->render('index');
        } finally {
            FilesystemHelper::removeDir($cache);
        }
    }

    public static function provideStagedSandboxRegistrations(): iterable
    {
        yield 'function' => [
            '{{ safe_function() }}',
            'function',
            static fn (Environment $twig, bool $allowed) => $twig->addFunction(new TwigFunction('safe_function', static fn (): string => 'function', ['always_allowed_in_sandbox' => $allowed])),
            SecurityNotAllowedFunctionError::class,
        ];
        yield 'filter' => [
            '{{ "filter"|safe_filter }}',
            'FILTER',
            static fn (Environment $twig, bool $allowed) => $twig->addFilter(new TwigFilter('safe_filter', 'strtoupper', ['always_allowed_in_sandbox' => $allowed])),
            SecurityNotAllowedFilterError::class,
        ];
        yield 'test' => [
            '{{ 2 is safe_test ? "test" : "no" }}',
            'test',
            static fn (Environment $twig, bool $allowed) => $twig->addTest(new TwigTest('safe_test', static fn (int $value): bool => 0 === $value % 2, ['always_allowed_in_sandbox' => $allowed])),
            SecurityNotAllowedTestError::class,
        ];
        yield 'tag' => [
            '{% safe_tag %}',
            'tag',
            static fn (Environment $twig, bool $allowed) => $twig->addTokenParser(new EnvironmentOptionsHashTokenParser($allowed)),
            SecurityNotAllowedTagError::class,
        ];
    }

    public function testAutoescapeCallbackIsResolvedOncePerTemplate(): void
    {
        $invocations = 0;
        $twig = new Environment(new ArrayLoader(['callback_once' => '{{ value }}']), [
            'autoescape' => static function (string $name) use (&$invocations) {
                ++$invocations;

                return 1 === $invocations ? 'html' : false;
            },
        ]);

        $class = $twig->getTemplateClass('callback_once');

        $this->assertSame($class, $twig->getTemplateClass('callback_once'));
        $this->assertSame('&lt;strong&gt;', $twig->render('callback_once', ['value' => '<strong>']));
        $this->assertSame('&lt;strong&gt;', $twig->render('callback_once', ['value' => '<strong>']));
        $this->assertSame(1, $invocations);
    }

    public function testResolvedAutoescapeStrategiesAreBounded(): void
    {
        $invocations = 0;
        $extension = new EscaperExtension(static function (string $name) use (&$invocations) {
            ++$invocations;

            return 'strategy'.$name;
        });

        for ($i = 0; $i <= 1000; ++$i) {
            $extension->getDefaultStrategy((string) $i);
        }

        $this->assertSame('strategy1', $extension->getDefaultStrategy('1'));
        $this->assertSame('strategy1000', $extension->getDefaultStrategy('1000'));
        $this->assertSame(1001, $invocations);

        $this->assertSame('strategy0', $extension->getDefaultStrategy('0'));
        $this->assertSame(1002, $invocations);
    }

    /**
     * @dataProvider provideAutoescapeReconfigurations
     */
    #[DataProvider('provideAutoescapeReconfigurations')]
    public function testChangingDefaultAutoescapeStrategyInvalidatesResolvedStrategies(string|bool $initialStrategy, string|bool $updatedStrategy, string $initialOutput, string $updatedOutput): void
    {
        $twig = new Environment(new ArrayLoader(['index' => '{{ value }}']), ['autoescape' => $initialStrategy]);
        $extension = $twig->getExtension(EscaperExtension::class);

        $initialClass = $twig->getTemplateClass('index');
        $this->assertSame($initialOutput, $twig->render('index', ['value' => '<strong>']));

        $extension->setDefaultStrategy($updatedStrategy);

        $this->assertNotSame($initialClass, $twig->getTemplateClass('index'));
        $this->assertSame($updatedOutput, $twig->render('index', ['value' => '<strong>']));
    }

    public static function provideAutoescapeReconfigurations(): iterable
    {
        yield 'disabled to html' => [false, 'html', '<strong>', '&lt;strong&gt;'];
        yield 'html to disabled' => ['html', false, '&lt;strong&gt;', '<strong>'];
    }

    public function testSupportedAutoescapeStrategiesHaveDistinctTemplateClasses(): void
    {
        $loader = new ArrayLoader(['index' => '']);
        $classes = [];

        foreach ([false, '', 'false', 'b:0;', 'string:0:'] as $strategy) {
            $classes[] = (new Environment($loader, ['autoescape' => $strategy]))->getTemplateClass('index');
        }

        $this->assertCount(\count($classes), array_unique($classes));
    }

    /**
     * @dataProvider provideFalseyInvalidAutoescapeStrategies
     */
    #[DataProvider('provideFalseyInvalidAutoescapeStrategies')]
    public function testFalseyInvalidAutoescapeCallbackReturnPreservesDisabledAutoescape(mixed $strategy): void
    {
        $twig = new Environment(new ArrayLoader(['index' => '{{ value }}']), [
            'autoescape' => static fn (string $name): mixed => $strategy,
        ]);

        $this->assertMatchesRegularExpression('/^__TwigTemplate_[a-f0-9]+$/', $twig->getTemplateClass('index'));
        $this->assertSame('<strong>', $twig->render('index', ['value' => '<strong>']));
    }

    public static function provideFalseyInvalidAutoescapeStrategies(): iterable
    {
        yield 'null' => [null];
        yield 'integer' => [0];
        yield 'float' => [0.0];
        yield 'array' => [[]];
    }

    /**
     * @dataProvider provideTruthyInvalidAutoescapeStrategies
     */
    #[DataProvider('provideTruthyInvalidAutoescapeStrategies')]
    public function testTruthyInvalidAutoescapeCallbackReturnDoesNotFailTemplateClassResolution(mixed $strategy): void
    {
        $twig = new Environment(new ArrayLoader(['index' => '']), [
            'autoescape' => static fn (string $name): mixed => $strategy,
        ]);

        $this->assertMatchesRegularExpression('/^__TwigTemplate_[a-f0-9]+$/', $twig->getTemplateClass('index'));
    }

    public static function provideTruthyInvalidAutoescapeStrategies(): iterable
    {
        yield 'true' => [true];
        yield 'integer' => [1];
        yield 'float' => [1.0];
        yield 'array' => [[null]];
        yield 'object' => [new \stdClass()];
        yield 'closure' => [static fn () => null];
    }

    /**
     * @dataProvider provideInvalidAutoescapeStrategiesWithDifferentEffectiveValues
     */
    #[DataProvider('provideInvalidAutoescapeStrategiesWithDifferentEffectiveValues')]
    public function testInvalidAutoescapeStrategiesWithDifferentEffectiveValuesHaveDistinctTemplateClasses(mixed $falseyStrategy, mixed $truthyStrategy): void
    {
        $loader = new ArrayLoader(['index' => '']);
        $falsey = new Environment($loader, ['autoescape' => static fn (string $name): mixed => $falseyStrategy]);
        $truthy = new Environment($loader, ['autoescape' => static fn (string $name): mixed => $truthyStrategy]);

        $this->assertNotSame($falsey->getTemplateClass('index'), $truthy->getTemplateClass('index'));
    }

    public static function provideInvalidAutoescapeStrategiesWithDifferentEffectiveValues(): iterable
    {
        yield 'integer' => [0, 1];
        yield 'float' => [0.0, 1.0];
        yield 'array' => [[], [null]];
    }

    public function testSameClassExtensionSandboxRegistrationsArePartOfTheTemplateClass(): void
    {
        $cache = $this->createCacheDirectory();
        $loader = new ArrayLoader(['index' => '{% safe_tag %}{{ safe_function() }}{{ "filter"|safe_filter }}{{ 2 is safe_test ? "test" : "no" }}']);

        try {
            $allowed = $this->createSandboxedEnvironment($loader, $cache);
            $allowed->addExtension(new EnvironmentOptionsHashExtension(true));
            $denied = $this->createSandboxedEnvironment($loader, $cache);
            $denied->addExtension(new EnvironmentOptionsHashExtension(false));

            $this->assertNotSame($allowed->getTemplateClass('index'), $denied->getTemplateClass('index'));
            $this->assertSame('tagfunctionFILTERtest', $allowed->render('index'));

            $this->expectException(SecurityNotAllowedTagError::class);
            $denied->render('index');
        } finally {
            FilesystemHelper::removeDir($cache);
        }
    }

    public function testUnrelatedCallableIdentityDoesNotChangeTheTemplateClass(): void
    {
        $cache = $this->createCacheDirectory();
        $loader = new ArrayLoader(['index' => '{{ dynamic() }} {{ "<b>"|safe_upper }}']);

        try {
            $first = $this->createSandboxedEnvironment($loader, $cache, ['safe_upper', 'escape'], ['dynamic'], 'html');
            $first->addFunction(new TwigFunction('dynamic', EnvironmentOptionsHashCallables::first(...)));
            $first->addFilter(new TwigFilter('safe_upper', 'strtoupper', ['is_safe_callback' => static fn (Node $arguments): array => ['html']]));

            $second = $this->createSandboxedEnvironment($loader, $cache, ['safe_upper', 'escape'], ['dynamic'], 'html');
            $second->addFunction(new TwigFunction('dynamic', EnvironmentOptionsHashCallables::equivalent(...)));
            $second->addFilter(new TwigFilter('safe_upper', 'strtoupper', ['is_safe_callback' => static fn (Node $arguments): array => ['html']]));

            $this->assertSame($first->getTemplateClass('index'), $second->getTemplateClass('index'));
            $this->assertSame('first <B>', $first->render('index'));
            $this->assertSame('first <B>', $second->render('index'));
        } finally {
            FilesystemHelper::removeDir($cache);
        }
    }

    public function testEquivalentConfigurationIsDeterministicAcrossProcesses(): void
    {
        $first = $this->runFixtureProcess();
        $second = $this->runFixtureProcess();

        $this->assertSame($first, $second);
        $this->assertMatchesRegularExpression('/^__TwigTemplate_[a-f0-9]+$/', $first);
    }

    private function createCacheDirectory(): string
    {
        $cache = sys_get_temp_dir().'/twig-environment-options-hash-'.bin2hex(random_bytes(6));
        mkdir($cache);

        return $cache;
    }

    private function createSandboxedEnvironment(ArrayLoader $loader, string $cache, array $filters = [], array $functions = [], string|bool $autoescape = false): Environment
    {
        $policy = new SecurityPolicy([], $filters, [], [], $functions);
        $policy->setStrict(true);

        $twig = new Environment($loader, ['cache' => $cache, 'autoescape' => $autoescape]);
        $twig->addExtension(new SandboxExtension($policy, true));

        return $twig;
    }

    private function runFixtureProcess(): string
    {
        $process = proc_open([\PHP_BINARY, __DIR__.'/Fixtures/environment_options_hash.php'], [
            ['pipe', 'r'],
            ['pipe', 'w'],
            ['pipe', 'w'],
        ], $pipes);
        $this->assertIsResource($process);

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $this->assertSame(0, proc_close($process), $stderr);

        return trim($stdout);
    }
}

final class EnvironmentOptionsHashCallables
{
    public static function htmlStrategy(string $name): string
    {
        return 'html';
    }

    public static function noAutoescapeStrategy(string $name): bool
    {
        return false;
    }

    public static function first(): string
    {
        return 'first';
    }

    public static function equivalent(): string
    {
        return 'first';
    }
}

final class EnvironmentOptionsHashExtension extends AbstractExtension
{
    public function __construct(private bool $alwaysAllowed)
    {
    }

    public function getTokenParsers(): array
    {
        return [new EnvironmentOptionsHashTokenParser($this->alwaysAllowed)];
    }

    public function getFilters(): array
    {
        return [new TwigFilter('safe_filter', 'strtoupper', ['always_allowed_in_sandbox' => $this->alwaysAllowed])];
    }

    public function getTests(): array
    {
        return [new TwigTest('safe_test', static fn (int $value): bool => 0 === $value % 2, ['always_allowed_in_sandbox' => $this->alwaysAllowed])];
    }

    public function getFunctions(): array
    {
        return [new TwigFunction('safe_function', static fn (): string => 'function', ['always_allowed_in_sandbox' => $this->alwaysAllowed])];
    }
}

final class EnvironmentOptionsHashTokenParser extends AbstractTokenParser
{
    public function __construct(private bool $alwaysAllowed)
    {
    }

    public function parse(Token $token): Node
    {
        $this->parser->getStream()->expect(Token::BLOCK_END_TYPE);

        return new TextNode('tag', $token->getLine());
    }

    public function getTag(): string
    {
        return 'safe_tag';
    }

    public function isAlwaysAllowedInSandbox(): bool
    {
        return $this->alwaysAllowed;
    }
}
