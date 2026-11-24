<?php

require dirname(__DIR__, 2).'/vendor/autoload.php';

use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\Extension\SandboxExtension;
use Twig\Loader\ArrayLoader;
use Twig\Node\Node;
use Twig\Sandbox\SecurityPolicy;
use Twig\TwigFilter;
use Twig\TwigFunction;

final class EnvironmentOptionsHashFixtureCallables
{
    public static function autoescape(string $name): string
    {
        return 'html';
    }

    public static function value(): string
    {
        return 'value';
    }
}

final class EnvironmentOptionsHashFixtureExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [new TwigFilter('safe', 'strtoupper', ['is_safe_callback' => static fn (Node $arguments): array => ['html']])];
    }

    public function getFunctions(): array
    {
        return [new TwigFunction('value', EnvironmentOptionsHashFixtureCallables::value(...), ['always_allowed_in_sandbox' => true])];
    }
}

$policy = new SecurityPolicy();
$policy->setStrict(true);
$twig = new Environment(new ArrayLoader(['index' => '{{ value()|safe }}']), ['autoescape' => EnvironmentOptionsHashFixtureCallables::autoescape(...)]);
$twig->addExtension(new SandboxExtension($policy, true));
$twig->addExtension(new EnvironmentOptionsHashFixtureExtension());

echo $twig->getTemplateClass('index');
