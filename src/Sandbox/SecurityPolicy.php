<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Sandbox;

/**
 * Represents a security policy which need to be enforced when sandbox mode is enabled.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
final class SecurityPolicy implements SecurityPolicyInterface
{
    /**
     * @var string[]
     */
    private array $allowedTags;
    /**
     * @var string[]
     */
    private array $allowedFilters;
    /**
     * @var array<string, string[]>
     */
    private array $allowedMethods;
    /**
     * @var array<string, string|string[]>
     */
    private array $allowedProperties;
    /**
     * @var string[]
     */
    private array $allowedFunctions;
    /**
     * @var string[]
     */
    private array $allowedTests;

    public function __construct(array $allowedTags = [], array $allowedFilters = [], array $allowedMethods = [], array $allowedProperties = [], array $allowedFunctions = [], array $allowedTests = [])
    {
        $this->allowedTags = $allowedTags;
        $this->allowedFilters = $allowedFilters;
        $this->setAllowedMethods($allowedMethods);
        $this->allowedProperties = $allowedProperties;
        $this->allowedFunctions = $allowedFunctions;
        $this->allowedTests = $allowedTests;
    }

    public function setAllowedTags(array $tags): void
    {
        $this->allowedTags = $tags;
    }

    public function setAllowedFilters(array $filters): void
    {
        $this->allowedFilters = $filters;
    }

    public function setAllowedMethods(array $methods): void
    {
        $this->allowedMethods = [];
        foreach ($methods as $class => $m) {
            $this->allowedMethods[$class] = array_map('strtolower', \is_array($m) ? $m : [$m]);
        }
    }

    public function setAllowedProperties(array $properties): void
    {
        $this->allowedProperties = $properties;
    }

    public function setAllowedFunctions(array $functions): void
    {
        $this->allowedFunctions = $functions;
    }

    public function setAllowedTests(array $tests): void
    {
        $this->allowedTests = $tests;
    }

    /**
     * Kept as a no-op for forward compatibility with 3.x code bases.
     *
     * In 3.x, this method toggled an opt-in to the 4.0 sandbox behavior for the
     * ``extends`` and ``use`` tags and the ``parent``, ``block``, and ``attribute``
     * functions. In 4.0 that behavior is the default and cannot be turned off, so
     * calling this method has no effect; it exists only to let user code run
     * unmodified on both 3.x and 4.0.
     */
    public function setStrict(bool $strict): void
    {
    }

    public function checkSecurity($tags, $filters, $functions, array $tests): void
    {
        foreach ($tags as $tag) {
            if (!\in_array($tag, $this->allowedTags, true)) {
                throw new SecurityNotAllowedTagError(\sprintf('Tag "%s" is not allowed.', $tag), $tag);
            }
        }

        foreach ($filters as $filter) {
            if (!\in_array($filter, $this->allowedFilters, true)) {
                throw new SecurityNotAllowedFilterError(\sprintf('Filter "%s" is not allowed.', $filter), $filter);
            }
        }

        foreach ($functions as $function) {
            if (!\in_array($function, $this->allowedFunctions, true)) {
                throw new SecurityNotAllowedFunctionError(\sprintf('Function "%s" is not allowed.', $function), $function);
            }
        }

        foreach ($tests as $test) {
            if (!\in_array($test, $this->allowedTests, true)) {
                throw new SecurityNotAllowedTestError(\sprintf('Test "%s" is not allowed.', $test), $test);
            }
        }
    }

    public function checkMethodAllowed($obj, $method): void
    {
        $allowed = false;
        $method = strtolower($method);
        foreach ($this->allowedMethods as $class => $methods) {
            if ($obj instanceof $class && \in_array($method, $methods, true)) {
                $allowed = true;
                break;
            }
        }

        if (!$allowed) {
            $class = $obj::class;
            throw new SecurityNotAllowedMethodError(\sprintf('Calling "%s" method on a "%s" object is not allowed.', $method, $class), $class, $method);
        }
    }

    public function checkPropertyAllowed($obj, $property): void
    {
        $allowed = false;
        foreach ($this->allowedProperties as $class => $properties) {
            if ($obj instanceof $class && \in_array($property, \is_array($properties) ? $properties : [$properties], true)) {
                $allowed = true;
                break;
            }
        }

        if (!$allowed) {
            $class = $obj::class;
            throw new SecurityNotAllowedPropertyError(\sprintf('Calling "%s" property on a "%s" object is not allowed.', $property, $class), $class, $property);
        }
    }
}
