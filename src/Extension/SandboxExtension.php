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
use Twig\Sandbox\SecurityChecker;
use Twig\Sandbox\SecurityNotAllowedMethodError;
use Twig\Sandbox\SecurityPolicyInterface;
use Twig\Source;

/**
 * This extension is the wiring behind "Twig\Sandbox\Sandbox" and should not
 * be used directly, use a "Sandbox" to render untrusted templates instead.
 *
 * @internal since Twig 3.29
 */
final class SandboxExtension extends AbstractExtension
{
    private SecurityChecker $checker;

    public function __construct(SecurityPolicyInterface $policy, $sandboxed = false)
    {
        $this->checker = new SecurityChecker($policy, (bool) $sandboxed);
    }

    public function getNodeVisitors(): array
    {
        return [new SandboxNodeVisitor()];
    }

    /**
     * @internal
     */
    public function getChecker(): SecurityChecker
    {
        return $this->checker;
    }

    public function isSandboxed(?Source $source = null): bool
    {
        return $this->checker->isSandboxed($source);
    }

    public function setSecurityPolicy(SecurityPolicyInterface $policy): void
    {
        $this->checker->setSecurityPolicy($policy);
    }

    public function getSecurityPolicy(): SecurityPolicyInterface
    {
        return $this->checker->getSecurityPolicy();
    }

    public function checkSecurity($tags, $filters, $functions, $tests = [], ?Source $source = null): void
    {
        $this->checker->checkSecurity($tags, $filters, $functions, $tests, $source);
    }

    public function checkMethodAllowed($obj, $method, int $lineno = -1, ?Source $source = null): void
    {
        $this->checker->checkMethodAllowed($obj, $method, $lineno, $source);
    }

    public function checkPropertyAllowed($obj, $property, int $lineno = -1, ?Source $source = null): void
    {
        $this->checker->checkPropertyAllowed($obj, $property, $lineno, $source);
    }

    /**
     * @throws SecurityNotAllowedMethodError
     */
    public function ensureToStringAllowed($obj, int $lineno = -1, ?Source $source = null)
    {
        return $this->checker->ensureToStringAllowed($obj, $lineno, $source);
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
        return $this->checker->ensureSpreadAllowed($obj, $lineno, $source);
    }
}
