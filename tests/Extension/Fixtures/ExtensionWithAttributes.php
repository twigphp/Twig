<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Tests\Extension\Fixtures;

use Twig\Attribute\AsTwigFilter;
use Twig\Attribute\AsTwigFunction;
use Twig\Attribute\AsTwigTest;
use Twig\DeprecatedCallableInfo;
use Twig\Environment;

class ExtensionWithAttributes
{
    #[AsTwigFilter(name: 'foo', isSafe: ['html'])]
    public function fooFilter(string|int $string): void
    {
    }

    #[AsTwigFilter('with_context_filter', needsContext: true)]
    public function withContextFilter(array $context, string $string): void
    {
    }

    #[AsTwigFilter('with_env_filter')]
    public function withEnvFilter(Environment $env, string $string): void
    {
    }

    #[AsTwigFilter('with_env_and_context_filter', needsContext: true)]
    public function withEnvAndContextFilter(Environment $env, array $context, array $data): void
    {
    }

    #[AsTwigFilter('with_sandbox_filter', needsIsSandboxed: true)]
    public function withSandboxFilter(bool $isSandboxed, string $string): void
    {
    }

    #[AsTwigFilter('always_allowed_filter', alwaysAllowedInSandbox: true)]
    public function alwaysAllowedFilter(string $string): void
    {
    }

    #[AsTwigFilter('variadic_filter')]
    public function variadicFilter(string ...$strings): void
    {
    }

    #[AsTwigFilter('deprecated_filter', deprecationInfo: new DeprecatedCallableInfo('foo/bar', '1.2'))]
    public function deprecatedFilter(string $string): void
    {
    }

    #[AsTwigFilter('deprecated_positional_filter', null, null, null, null, null, null, null, null, new DeprecatedCallableInfo('foo/bar', '1.2'))]
    public function deprecatedPositionalFilter(string $string): void
    {
    }

    #[AsTwigFilter('pattern_*_filter')]
    public function patternFilter(string $string): void
    {
    }

    #[AsTwigFunction(name: 'foo', isSafe: ['html'])]
    public function fooFunction(string|int $string): void
    {
    }

    #[AsTwigFunction('with_context_function', needsContext: true)]
    public function withContextFunction(array $context, string $string): void
    {
    }

    #[AsTwigFunction('with_env_function')]
    public function withEnvFunction(Environment $env, string $string): void
    {
    }

    #[AsTwigFunction('with_env_and_context_function', needsContext: true)]
    public function withEnvAndContextFunction(Environment $env, array $context, string $string): void
    {
    }

    #[AsTwigFunction('with_sandbox_function', needsIsSandboxed: true)]
    public function withSandboxFunction(bool $isSandboxed, string $string): void
    {
    }

    #[AsTwigFunction('always_allowed_function', alwaysAllowedInSandbox: true)]
    public function alwaysAllowedFunction(string $string): void
    {
    }

    #[AsTwigFunction('no_arg_function')]
    public function noArgFunction(): void
    {
    }

    #[AsTwigFunction('variadic_function')]
    public function variadicFunction(string ...$strings): void
    {
    }

    #[AsTwigFunction('deprecated_function', deprecationInfo: new DeprecatedCallableInfo('foo/bar', '1.2'))]
    public function deprecatedFunction(string $string): void
    {
    }

    #[AsTwigFunction('deprecated_positional_function', null, null, null, null, null, null, new DeprecatedCallableInfo('foo/bar', '1.2'))]
    public function deprecatedPositionalFunction(string $string): void
    {
    }

    #[AsTwigTest(name: 'foo')]
    public function fooTest(string|int $value): void
    {
    }

    #[AsTwigTest('variadic_test')]
    public function variadicTest(string ...$value): void
    {
    }

    #[AsTwigTest('with_context_test', needsContext: true)]
    public function withContextTest(array $context, $argument): void
    {
    }

    #[AsTwigTest('with_env_test')]
    public function withEnvTest(Environment $env, $argument): void
    {
    }

    #[AsTwigTest('with_env_and_context_test', needsContext: true)]
    public function withEnvAndContextTest(Environment $env, array $context, $argument): void
    {
    }

    #[AsTwigTest('with_sandbox_test', needsIsSandboxed: true)]
    public function withSandboxTest(bool $isSandboxed, $argument): void
    {
    }

    #[AsTwigTest('always_allowed_test', alwaysAllowedInSandbox: true)]
    public function alwaysAllowedTest($value): void
    {
    }

    #[AsTwigTest('deprecated_test', deprecationInfo: new DeprecatedCallableInfo('foo/bar', '1.2'))]
    public function deprecatedTest($value, $argument): void
    {
    }

    #[AsTwigTest('deprecated_positional_test', null, null, null, null, new DeprecatedCallableInfo('foo/bar', '1.2'))]
    public function deprecatedPositionalTest($value, $argument): void
    {
    }
}
