<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Extension;

use Twig\NodeVisitor\SandboxNodeVisitor;
use Twig\Sandbox\SecurityNotAllowedMethodError;
use Twig\Sandbox\SecurityNotAllowedPropertyError;
use Twig\Sandbox\SecurityPolicyInterface;
use Twig\Sandbox\SourcePolicyInterface;
use Twig\Source;
use Twig\TokenParser\SandboxTokenParser;

final class SandboxExtension extends AbstractExtension
{
    private $sandboxedGlobally;
    private $sandboxed;
    private $policy;
    private $sourcePolicy;

    public function __construct(SecurityPolicyInterface $policy, $sandboxed = false, ?SourcePolicyInterface $sourcePolicy = null)
    {
        if (null !== $sourcePolicy) {
            trigger_deprecation('twig/twig', '3.27.0', 'The "%s" interface is deprecated with no replacement, do not pass an instance to "%s".', SourcePolicyInterface::class, self::class);
        }

        $this->policy = $policy;
        $this->sandboxedGlobally = $sandboxed;
        $this->sourcePolicy = $sourcePolicy;
    }

    public function getTokenParsers(): array
    {
        return [new SandboxTokenParser()];
    }

    public function getNodeVisitors(): array
    {
        return [new SandboxNodeVisitor()];
    }

    public function enableSandbox(): void
    {
        $this->sandboxed = true;
    }

    public function disableSandbox(): void
    {
        $this->sandboxed = false;
    }

    public function isSandboxed(?Source $source = null): bool
    {
        return $this->sandboxedGlobally || $this->sandboxed || $this->isSourceSandboxed($source);
    }

    public function isSandboxedGlobally(): bool
    {
        return $this->sandboxedGlobally;
    }

    private function isSourceSandboxed(?Source $source): bool
    {
        if (null === $source || null === $this->sourcePolicy) {
            return false;
        }

        return $this->sourcePolicy->enableSandbox($source);
    }

    public function setSecurityPolicy(SecurityPolicyInterface $policy): void
    {
        $this->policy = $policy;
    }

    public function getSecurityPolicy(): SecurityPolicyInterface
    {
        return $this->policy;
    }

    public function checkSecurity($tags, $filters, $functions, $tests = [], $source = null): void
    {
        // BC: previous signature was checkSecurity($tags, $filters, $functions, ?Source $source = null);
        // detect a legacy call where the 4th positional argument was the Source.
        if ($tests instanceof Source || (null === $tests && \func_num_args() < 5)) {
            trigger_deprecation('twig/twig', '3.28', 'Passing a "Twig\Source" as the 4th argument of "%s()" is deprecated; pass an array of tests instead.', __METHOD__);
            $source = $tests;
            $tests = [];
        }

        if (!$this->isSandboxed($source)) {
            return;
        }

        if ((new \ReflectionMethod($this->policy, 'checkSecurity'))->getNumberOfParameters() >= 4) {
            $this->policy->checkSecurity($tags, $filters, $functions, $tests);

            return;
        }

        trigger_deprecation('twig/twig', '3.28', 'The "%s::checkSecurity()" method will take a 4th "array $tests" argument in 4.0; not declaring it is deprecated.', $this->policy::class);

        $this->policy->checkSecurity($tags, $filters, $functions);
    }

    public function checkMethodAllowed($obj, $method, int $lineno = -1, ?Source $source = null): void
    {
        if ($this->isSandboxed($source)) {
            try {
                $this->policy->checkMethodAllowed($obj, $method);
            } catch (SecurityNotAllowedMethodError $e) {
                $e->setSourceContext($source);
                $e->setTemplateLine($lineno);

                throw $e;
            }
        }
    }

    public function checkPropertyAllowed($obj, $property, int $lineno = -1, ?Source $source = null): void
    {
        if ($this->isSandboxed($source)) {
            try {
                $this->policy->checkPropertyAllowed($obj, $property);
            } catch (SecurityNotAllowedPropertyError $e) {
                $e->setSourceContext($source);
                $e->setTemplateLine($lineno);

                throw $e;
            }
        }
    }

    /**
     * @throws SecurityNotAllowedMethodError
     */
    public function ensureToStringAllowed($obj, int $lineno = -1, ?Source $source = null)
    {
        return $this->doEnsureToStringAllowed($obj, $lineno, $source, new \SplObjectStorage());
    }

    /**
     * Materialises a spread operand and runs the policy on every element.
     *
     * @internal
     *
     * @throws SecurityNotAllowedMethodError
     */
    public function ensureSpreadAllowed(iterable $obj, int $lineno = -1, ?Source $source = null): array
    {
        $seen = new \SplObjectStorage();
        if ($obj instanceof \Traversable) {
            $seen[$obj] = true;
            $obj = iterator_to_array($obj);
        }

        $this->ensureToStringAllowedForArray($obj, $lineno, $source, $seen);

        return $obj;
    }

    private function doEnsureToStringAllowed($obj, int $lineno, ?Source $source, \SplObjectStorage $seen)
    {
        if (\is_array($obj)) {
            $this->ensureToStringAllowedForArray($obj, $lineno, $source, $seen);

            return $obj;
        }

        if (!$this->isSandboxed($source)) {
            return $obj;
        }

        if ($obj instanceof \Stringable) {
            try {
                $this->policy->checkMethodAllowed($obj, '__toString');
            } catch (SecurityNotAllowedMethodError $e) {
                $e->setSourceContext($source);
                $e->setTemplateLine($lineno);

                throw $e;
            }
        }

        // Elements yielded by a Traversable may be string-coerced downstream
        // (e.g. by `join`/`replace`), bypassing the policy. Check them now.
        if ($obj instanceof \Traversable) {
            if (isset($seen[$obj])) {
                return $obj;
            }
            $seen[$obj] = true;

            // IteratorAggregate::getIterator() is idempotent, so we can walk
            // the elements and return the original object: host code typed
            // against a specific class (e.g. FormView) keeps working.
            if ($obj instanceof \IteratorAggregate) {
                foreach ($obj as $v) {
                    $this->doEnsureToStringAllowed($v, $lineno, $source, $seen);
                }

                return $obj;
            }

            // Single-pass Iterator/Generator: materialise to validate.
            $array = iterator_to_array($obj);
            $this->ensureToStringAllowedForArray($array, $lineno, $source, $seen);

            if (!$obj instanceof \Stringable) {
                return $array;
            }
        }

        return $obj;
    }

    private function ensureToStringAllowedForArray(array $obj, int $lineno, ?Source $source, \SplObjectStorage $seen, array &$stack = []): void
    {
        foreach ($obj as $k => $v) {
            if (!$v) {
                continue;
            }

            if (!\is_array($v)) {
                $this->doEnsureToStringAllowed($v, $lineno, $source, $seen);
                continue;
            }

            if ($r = \ReflectionReference::fromArrayElement($obj, $k)) {
                if (isset($stack[$r->getId()])) {
                    continue;
                }

                $stack[$r->getId()] = true;
            }

            $this->ensureToStringAllowedForArray($v, $lineno, $source, $seen, $stack);
        }
    }
}
