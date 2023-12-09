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

use Twig\Markup;
use Twig\Template;

/**
 * Represents a security policy which need to be enforced when sandbox mode is enabled.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
final class SecurityPolicy implements SecurityPolicyInterface
{
    private $allowedTags;
    private $allowedFilters;
    private $allowedMethods;
    private $allowedProperties;
    private $allowedFunctions;

    public function __construct(array $allowedTags = [], array $allowedFilters = [], array $allowedMethods = [], array $allowedProperties = [], array $allowedFunctions = [])
    {
        $this->allowedTags = $allowedTags;
        $this->allowedFilters = $allowedFilters;
        $this->setAllowedMethods($allowedMethods);
        $this->setAllowedProperties($allowedProperties);
        $this->allowedFunctions = $allowedFunctions;
    }

    public function setAllowedTags(array $tags)
    {
        $this->allowedTags = $tags;
    }

    public function setAllowedFilters(array $filters)
    {
        $this->allowedFilters = $filters;
    }

    public function setAllowedMethods(array $methods)
    {
        $this->allowedMethods = new MemberMatcher($methods);
    }

    public function setAllowedProperties(array $properties)
    {
        $this->allowedProperties = new MemberMatcher($properties);
    }

    public function setAllowedFunctions(array $functions)
    {
        $this->allowedFunctions = $functions;
    }

    public function checkSecurity($tags, $filters, $functions)
    {
        foreach ($tags as $tag) {
            if (!\in_array($tag, $this->allowedTags)) {
                throw new SecurityNotAllowedTagError(sprintf('Tag "%s" is not allowed.', $tag), $tag);
            }
        }

        foreach ($filters as $filter) {
            if (!\in_array($filter, $this->allowedFilters)) {
                throw new SecurityNotAllowedFilterError(sprintf('Filter "%s" is not allowed.', $filter), $filter);
            }
        }

        foreach ($functions as $function) {
            if (!\in_array($function, $this->allowedFunctions)) {
                throw new SecurityNotAllowedFunctionError(sprintf('Function "%s" is not allowed.', $function), $function);
            }
        }
    }

    public function checkMethodAllowed($obj, $method)
    {
        if ($obj instanceof Template || $obj instanceof Markup) {
            return;
        }

        if (!$this->allowedMethods->isAllowed($obj, $method)) {
            $class = \get_class($obj);
            $method = strtr($method, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz');
            throw new SecurityNotAllowedMethodError(sprintf('Calling "%s" method on a "%s" object is not allowed.', $method, $class), $class, $method);
        }
    }

    public function checkPropertyAllowed($obj, $property)
    {
        if (!$this->allowedProperties->isAllowed($obj, $property)) {
            $class = \get_class($obj);
            throw new SecurityNotAllowedPropertyError(sprintf('Calling "%s" property on a "%s" object is not allowed.', $property, $class), $class, $property);
        }
    }
}

class_alias('Twig\Sandbox\SecurityPolicy', 'Twig_Sandbox_SecurityPolicy');
