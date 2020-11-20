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
use Twig\Source;
use Twig\TokenParser\SandboxTokenParser;

/**
 * Class SandboxExtension
 * @package Twig\Extension
 */
final class SandboxExtension extends AbstractExtension
{
    /**
     * @var false|mixed
     */
    private $sandboxedGlobally;
    /**
     * @var
     */
    private $sandboxed;
    /**
     * @var SecurityPolicyInterface
     */
    private $policy;

    /**
     * SandboxExtension constructor.
     * @param SecurityPolicyInterface $policy
     * @param false $sandboxed
     */
    public function __construct(SecurityPolicyInterface $policy, $sandboxed = false)
    {
        $this->policy = $policy;
        $this->sandboxedGlobally = $sandboxed;
    }

    /**
     * @return SandboxTokenParser[]
     */
    public function getTokenParsers(): array
    {
        return [new SandboxTokenParser()];
    }

    /**
     * @return SandboxNodeVisitor[]
     */
    public function getNodeVisitors(): array
    {
        return [new SandboxNodeVisitor()];
    }

    /**
     *
     */
    public function enableSandbox(): void
    {
        $this->sandboxed = true;
    }

    /**
     *
     */
    public function disableSandbox(): void
    {
        $this->sandboxed = false;
    }

    /**
     * @return bool
     */
    public function isSandboxed(): bool
    {
        return $this->sandboxedGlobally || $this->sandboxed;
    }

    /**
     * @return bool
     */
    public function isSandboxedGlobally(): bool
    {
        return $this->sandboxedGlobally;
    }

    /**
     * @param SecurityPolicyInterface $policy
     */
    public function setSecurityPolicy(SecurityPolicyInterface $policy)
    {
        $this->policy = $policy;
    }

    /**
     * @return SecurityPolicyInterface
     */
    public function getSecurityPolicy(): SecurityPolicyInterface
    {
        return $this->policy;
    }

    /**
     * @param $tags
     * @param $filters
     * @param $functions
     * @throws \Twig\Sandbox\SecurityError
     */
    public function checkSecurity($tags, $filters, $functions): void
    {
        if ($this->isSandboxed()) {
            $this->policy->checkSecurity($tags, $filters, $functions);
        }
    }

    /**
     * @param $obj
     * @param $method
     * @param int $lineno
     * @param Source|null $source
     * @throws SecurityNotAllowedMethodError
     */
    public function checkMethodAllowed($obj, $method, int $lineno = -1, Source $source = null): void
    {
        if ($this->isSandboxed()) {
            try {
                $this->policy->checkMethodAllowed($obj, $method);
            } catch (SecurityNotAllowedMethodError $e) {
                $e->setSourceContext($source);
                $e->setTemplateLine($lineno);

                throw $e;
            }
        }
    }

    /**
     * @param $obj
     * @param $method
     * @param int $lineno
     * @param Source|null $source
     * @throws SecurityNotAllowedPropertyError
     */
    public function checkPropertyAllowed($obj, $method, int $lineno = -1, Source $source = null): void
    {
        if ($this->isSandboxed()) {
            try {
                $this->policy->checkPropertyAllowed($obj, $method);
            } catch (SecurityNotAllowedPropertyError $e) {
                $e->setSourceContext($source);
                $e->setTemplateLine($lineno);

                throw $e;
            }
        }
    }

    /**
     * @param $obj
     * @param int $lineno
     * @param Source|null $source
     * @return mixed
     * @throws SecurityNotAllowedMethodError
     */
    public function ensureToStringAllowed($obj, int $lineno = -1, Source $source = null)
    {
        if ($this->isSandboxed() && \is_object($obj) && method_exists($obj, '__toString')) {
            try {
                $this->policy->checkMethodAllowed($obj, '__toString');
            } catch (SecurityNotAllowedMethodError $e) {
                $e->setSourceContext($source);
                $e->setTemplateLine($lineno);

                throw $e;
            }
        }

        return $obj;
    }
}
