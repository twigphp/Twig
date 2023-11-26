<?php

namespace Twig\Tests\Extension\Fixtures;

use Twig\Environment;
use Twig\Extension\Attribute\AsTwigFilter;
use Twig\Extension\Attribute\AsTwigFunction;
use Twig\Extension\Attribute\AsTwigTest;
use Twig\Extension\Extension;

class AttributeExtension extends Extension
{
    #[AsTwigFilter]
    #[AsTwigFilter(name: 'foo')]
    public function fooFilter(string $string)
    {
    }

    #[AsTwigFilter]
    public function withContextFilter(array $context, string $string)
    {
    }

    #[AsTwigFilter]
    public function withEnvFilter(Environment $env, string $string)
    {
    }

    #[AsTwigFilter]
    public function withEnvAndContextFilter(Environment $env, array $context, string $string)
    {
    }

    #[AsTwigFilter]
    public function variadicFilter(string ...$strings)
    {
    }

    #[AsTwigFilter(options: ['deprecated' => true, 'alternative' => 'bar'])]
    public function deprecatedFilter(string $string)
    {
    }

    #[AsTwigFunction]
    #[AsTwigFunction(name: 'foo')]
    public function fooFunction(string $string)
    {
    }

    #[AsTwigFunction]
    public function withContextFunction(array $context, string $string)
    {
    }

    #[AsTwigFunction]
    public function withEnvFunction(Environment $env, string $string)
    {
    }

    #[AsTwigFunction]
    public function withEnvAndContextFunction(Environment $env, array $context, string $string)
    {
    }

    #[AsTwigFunction]
    public function variadicFunction(string ...$strings)
    {
    }

    #[AsTwigFunction(options: ['deprecated' => true, 'alternative' => 'bar'])]
    public function deprecatedFunction(string $string)
    {
    }

    #[AsTwigTest]
    #[AsTwigTest(name: 'foo')]
    public function fooTest(string $string)
    {
    }

    #[AsTwigTest]
    public function variadicTest(string ...$strings)
    {
    }

    #[AsTwigTest(options: ['deprecated' => true, 'alternative' => 'bar'])]
    public function deprecatedTest(string $strings)
    {
    }
}
