<?php

namespace Twig\Tests\Extension\Fixtures;

use Twig\Environment;
use Twig\Extension\Attribute\AsTwigExtension;
use Twig\Extension\Attribute\AsTwigFilter;
use Twig\Extension\Attribute\AsTwigFunction;
use Twig\Extension\Attribute\AsTwigTest;

#[AsTwigExtension]
class ExtensionWithAttributes
{
    #[AsTwigFilter(name: 'foo')]
    public function fooFilter(string $string)
    {
    }

    #[AsTwigFilter('with_context_filter')]
    public function withContextFilter(array $context, string $string)
    {
    }

    #[AsTwigFilter('with_env_filter')]
    public function withEnvFilter(Environment $env, string $string)
    {
    }

    #[AsTwigFilter('with_env_and_context_filter')]
    public function withEnvAndContextFilter(Environment $env, array $context, string $string)
    {
    }

    #[AsTwigFilter('no_arg_filter')]
    public function noArgFilter()
    {
    }

    #[AsTwigFilter('variadic_filter')]
    public function variadicFilter(string ...$strings)
    {
    }

    #[AsTwigFilter('deprecated_filter', deprecated: true, alternative: 'bar')]
    public function deprecatedFilter(string $string)
    {
    }

    #[AsTwigFunction(name: 'foo')]
    public function fooFunction(string $string)
    {
    }

    #[AsTwigFunction('with_context_function')]
    public function withContextFunction(array $context, string $string)
    {
    }

    #[AsTwigFunction('with_env_function')]
    public function withEnvFunction(Environment $env, string $string)
    {
    }

    #[AsTwigFunction('with_env_and_context_function')]
    public function withEnvAndContextFunction(Environment $env, array $context, string $string)
    {
    }

    #[AsTwigFunction('no_arg_function')]
    public function noArgFunction()
    {
    }

    #[AsTwigFunction('variadic_function')]
    public function variadicFunction(string ...$strings)
    {
    }

    #[AsTwigFunction('deprecated_function', deprecated: true, alternative: 'bar')]
    public function deprecatedFunction(string $string)
    {
    }

    #[AsTwigTest(name: 'foo')]
    public function fooTest(string $string)
    {
    }

    #[AsTwigTest('variadic_test')]
    public function variadicTest(string ...$strings)
    {
    }

    #[AsTwigTest('deprecated_test', deprecated: true, alternative: 'bar')]
    public function deprecatedTest(string $string)
    {
    }
}
