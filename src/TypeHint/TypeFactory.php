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
 * Provides an entrypoint to build instances of @see TypeInterface
 *
 * @author Joshua Behrens <code@joshua-behrens.de>
 */
abstract class TypeFactory
{
    private static array $plainTypeCache = [];

    private static array $objectTypeCache = [];

    public static function createTypeFromText(string $type): ?TypeInterface
    {
        $types = [];

        foreach (\explode('|', $type) as $propertyType) {
            if (\str_starts_with($propertyType, '\\')) {
                try {
                    $types[] = self::createObjectType($propertyType);
                } catch (\Throwable) {
                    continue;
                }
            } else {
                $types[] = self::createPlainType($propertyType);
            }
        }

        return static::createTypeFromCollection($types);
    }

    public static function createTypeFromCollection(array $types): ?TypeInterface
    {
        if ($types === []) {
            return null;
        }

        return \count($types) === 1 ? $types[0] : new UnionType($types);
    }

    private static function createPlainType(string $type): Type
    {
        return self::$plainTypeCache[$type] ??= new Type($type);
    }

    /**
     * @throws \ReflectionException
     */
    private static function createObjectType(string $class): ObjectType
    {
        return self::$objectTypeCache[$class] ??= new ObjectType(new \ReflectionClass(\ltrim($class, '\\')));
    }
}
