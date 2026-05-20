<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Tests\Resources;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\SandboxExtension;
use Twig\Loader\ArrayLoader;
use Twig\Sandbox\SecurityNotAllowedPropertyError;
use Twig\Sandbox\SecurityPolicy;
use Twig\Sandbox\SourcePolicyInterface;
use Twig\Source;
use Twig\Template;
use Twig\TemplateWrapper;

require_once __DIR__.'/../../src/Resources/core.php';

/**
 * Tests that the deprecated wrappers in src/Resources/core.php still enforce
 * sandbox checks.
 *
 * @group legacy
 */
class LegacyCoreTest extends TestCase
{
    public function testTwigSortFilterEnforcesGlobalSandbox()
    {
        $env = $this->createSandboxedEnvironment(true);
        $template = new LegacyCoreTestTemplate($env, 'index.twig');

        $this->expectException(RuntimeError::class);
        $this->expectExceptionMessageMatches('/must be a Closure in sandbox mode/');

        $template->callLegacySort(['b', 'a'], 'strnatcasecmp');
    }

    public function testTwigSortFilterRecoversSourceForSourcePolicy()
    {
        $env = $this->createSandboxedEnvironment(false, new class implements SourcePolicyInterface {
            public function enableSandbox(Source $source): bool
            {
                return 'sandboxed.twig' === $source->getName();
            }
        });

        $sandboxedTemplate = new LegacyCoreTestTemplate($env, 'sandboxed.twig');
        $trustedTemplate = new LegacyCoreTestTemplate($env, 'trusted.twig');

        // Trusted template: non-Closure callable is allowed (only a deprecation is triggered).
        $this->assertSame(['a', 'b'], array_values($trustedTemplate->callLegacySort(['b', 'a'], 'strnatcasecmp')));

        // Sandboxed template: non-Closure callable is rejected thanks to Source recovery.
        $this->expectException(RuntimeError::class);
        $this->expectExceptionMessageMatches('/must be a Closure in sandbox mode/');
        $sandboxedTemplate->callLegacySort(['b', 'a'], 'strnatcasecmp');
    }

    public function testTwigArrayFilterRecoversSourceForSourcePolicy()
    {
        $env = $this->createSandboxedEnvironment(false, new class implements SourcePolicyInterface {
            public function enableSandbox(Source $source): bool
            {
                return 'sandboxed.twig' === $source->getName();
            }
        });

        $template = new LegacyCoreTestTemplate($env, 'sandboxed.twig');

        $this->expectException(RuntimeError::class);
        $this->expectExceptionMessageMatches('/must be a Closure in sandbox mode/');
        iterator_to_array($template->callLegacyArrayFilter(['a', 'b'], 'is_string'));
    }

    public function testTwigArrayMapRecoversSourceForSourcePolicy()
    {
        $env = $this->createSandboxedEnvironment(false, new class implements SourcePolicyInterface {
            public function enableSandbox(Source $source): bool
            {
                return 'sandboxed.twig' === $source->getName();
            }
        });

        $template = new LegacyCoreTestTemplate($env, 'sandboxed.twig');

        $this->expectException(RuntimeError::class);
        $this->expectExceptionMessageMatches('/must be a Closure in sandbox mode/');
        $template->callLegacyArrayMap(['a', 'b'], 'strtoupper');
    }

    public function testTwigArrayColumnEnforcesSandbox()
    {
        $env = $this->createSandboxedEnvironment(true);
        $template = new LegacyCoreTestTemplate($env, 'index.twig');

        $this->expectException(SecurityNotAllowedPropertyError::class);
        $template->callLegacyArrayColumn([new LegacyColumnObject()], 'bar');
    }

    public function testTwigArrayReduceRecoversSourceForSourcePolicy()
    {
        $env = $this->createSandboxedEnvironment(false, new class implements SourcePolicyInterface {
            public function enableSandbox(Source $source): bool
            {
                return 'sandboxed.twig' === $source->getName();
            }
        });

        $template = new LegacyCoreTestTemplate($env, 'sandboxed.twig');

        $this->expectException(RuntimeError::class);
        $this->expectExceptionMessageMatches('/must be a Closure in sandbox mode/');
        $template->callLegacyArrayReduce([1, 2], 'intval');
    }

    private function createSandboxedEnvironment(bool $globallySandboxed, ?SourcePolicyInterface $sourcePolicy = null): Environment
    {
        $env = new Environment(new ArrayLoader([]), ['cache' => false, 'autoescape' => false]);
        $env->addExtension(new SandboxExtension(new SecurityPolicy(), $globallySandboxed, $sourcePolicy));

        return $env;
    }
}

class LegacyCoreTestTemplate extends Template
{
    public function __construct(Environment $env, private string $name)
    {
        parent::__construct($env);
    }

    public function callLegacySort($array, $arrow)
    {
        return twig_sort_filter($this->env, $array, $arrow);
    }

    public function callLegacyArrayFilter($array, $arrow)
    {
        return twig_array_filter($this->env, $array, $arrow);
    }

    public function callLegacyArrayMap($array, $arrow)
    {
        return twig_array_map($this->env, $array, $arrow);
    }

    public function callLegacyArrayReduce($array, $arrow, $initial = null)
    {
        return twig_array_reduce($this->env, $array, $arrow, $initial);
    }

    public function callLegacyArrayColumn($array, $name, $index = null)
    {
        return twig_array_column($this->env, $array, $name, $index);
    }

    public function getTemplateName(): string
    {
        return $this->name;
    }

    public function getDebugInfo(): array
    {
        return [];
    }

    public function getSourceContext(): Source
    {
        return new Source('', $this->name);
    }

    protected function doGetParent(array $context): bool|string|Template|TemplateWrapper
    {
        return false;
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        return [];
    }
}

class LegacyColumnObject
{
    public $bar = 'bar';
}
