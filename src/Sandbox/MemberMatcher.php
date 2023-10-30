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
 * Allows for flexible wildcard support in allowedMethods and allowedProperties in SecurityPolicy.
 * - Class can be specified as wildcard `* => [...]` in order to allow those methods/properties for all classes.
 * - Method/property can be specified as wildcard eg. `\DateTime => '*'` in order to allow all methods/properties for that class.
 * - Method/property can also be specified with a trailing wildcard to allow all methods/properties with a certain prefix, eg. `\DateTime => ['get*', ...]` in order to allow all methods/properties that start with `get`.
 *
 * @author Yaakov Saxon <ysaxon@gmail.com>
 */
final class MemberMatcher
{
    private $allowedMembers;
    private $cache = [];

    public function __construct(array $allowedMembers)
    {
        $normalizedMembers = [];
        foreach ($allowedMembers as $class => $members) {
            if (!\is_array($members)) {
                $normalizedMembers[$class][] = strtolower($members);
            } else {
                foreach ($members as $index => $member) {
                    $normalizedMembers[$class][$index] = strtolower($member);
                }
            }
        }
        $this->allowedMembers = $normalizedMembers;
    }

    public function isAllowed($obj, string $member): bool
    {
        $cacheKey = get_class($obj) . "::" . $member;

        // Check cache first
        if (isset($this->cache[$cacheKey])) {
            return true;
        }

        $member = strtolower($member); // normalize member name

        foreach ($this->allowedMembers as $class => $members) {
            if ('*' === $class || $obj instanceof $class) {
                foreach ($members as $allowedMember) {
                    if ('*' === $allowedMember) {
                        $this->cache[$cacheKey] = true;

                        return true;
                    }
                    if ($allowedMember === $member) {
                        $this->cache[$cacheKey] = true;

                        return true;
                    }
                    // if allowedMember ends with a *, check if the member starts with the allowedMember
                    if ('*' === substr($allowedMember, -1) && substr($member, 0, \strlen($allowedMember) - 1) === rtrim($allowedMember, '*')) {
                        $this->cache[$cacheKey] = true;

                        return true;
                    }
                }
            }
        }

        // If we reach here, the member is not allowed
        return false;
    }
}
