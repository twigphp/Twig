<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Twig\TypeHint;

/**
 * This describes a type of an instance of an object.
 *
 * @author Joshua Behrens <code@joshua-behrens.de>
 */
class ObjectType extends Type
{
    private \ReflectionClass $reflectionClass;

    /**
     * @var array<string, Type>
     */
    private array $properties = [];

    /**
     * @var array<string, Type>
     */
    private array $methods = [];

    public function __construct(\ReflectionClass $reflectionClass)
    {
        parent::__construct($reflectionClass->getName());
        $this->reflectionClass = $reflectionClass;
    }

    public function getAttributeType(string|int $attribute): ?TypeInterface
    {
        return $this->getPropertyType((string) $attribute)
            ?? $this->getMethodType((string) $attribute)
            ?? $this->getMethodType('get' . $attribute)
            ?? $this->getMethodType('is' . $attribute)
            ?? $this->getMethodType('has' . $attribute);
    }

    private function getPropertyType(string $name): ?TypeInterface
    {
        if (\array_key_exists($name, $this->properties)) {
            return $this->properties[$name];
        }

        return $this->properties[$name] = $this->createPropertyType($name);
    }

    private function createPropertyType(string $name): ?TypeInterface
    {
        if (!$this->reflectionClass->hasProperty($name)) {
            return null;
        }

        try {
            $property = $this->reflectionClass->getProperty($name);

            if (!$property->isPublic()) {
                return null;
            }

            return $this->createType($property->getType());
        } catch (\Throwable) {
            return null;
        }
    }

    private function getMethodType(string $name): ?TypeInterface
    {
        if (\array_key_exists($name, $this->methods)) {
            return $this->methods[$name];
        }

        return $this->methods[$name] = $this->createMethodType($name);
    }

    private function createMethodType(string $name): ?TypeInterface
    {
        if (!$this->reflectionClass->hasMethod($name)) {
            $this->methods[$name] = null;

            return null;
        }

        try {
            $method = $this->reflectionClass->getMethod($name);

            if (!$method->isPublic()) {
                return null;
            }

            return $this->createType($method->getReturnType());
        } catch (\Throwable) {
            return null;
        }
    }

    private function createType(?\ReflectionType $type): ?TypeInterface
    {
        if ($type === null) {
            return null;
        }

        return TypeFactory::createTypeFromText((string) $type);
    }
}
