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
 * Allows for flexible wildcard supported method and property matching in Security Policies.
 *
 * @author Yaakov Saxon <ysaxon@gmail.com>
 */
final class MemberMatcher
{
    private $allowedMethods;
    private $cache = [];

    public function __construct(array $allowedMethods)
    {
        foreach ($allowedMethods as $class => $methods) {
            foreach ($methods as $index => $method) {
                $allowedMethods[$class][$index] = strtolower($method);
            }
        }
        $this->allowedMethods = $allowedMethods;
    }


    public function isAllowed($obj, string $method): bool
    {
        $cacheKey = get_class($obj) . "::" . $method;

        // Check cache first
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $method = strtolower($method); // normalize method name

        foreach ($this->allowedMethods as $class => $methods) {
            if ($class === '*' || $obj instanceof $class) {
                foreach ($methods as $allowedMethod) {
                    if ($allowedMethod === '*') {
                        $this->cache[$cacheKey] = true;
                        return true;
                    }
                    if ($allowedMethod === $method) {
                        $this->cache[$cacheKey] = true;
                        return true;
                    }
                    //if allowedMethod ends with a *, check if the method starts with the allowedMethod
                    if (substr($allowedMethod, -1) === '*' && substr($method, 0, strlen($allowedMethod) - 1) === rtrim($allowedMethod, '*')) {
                        $this->cache[$cacheKey] = true;
                        return true;
                    }
                }
            }
        }

        // If we reach here, the method is not allowed
        $this->cache[$cacheKey] = false;
        return false;
    }
}
