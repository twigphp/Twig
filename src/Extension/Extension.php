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

use Twig\Environment;
use Twig\Extension\Attribute\AsTwigFilter;
use Twig\Extension\Attribute\AsTwigFunction;
use Twig\Extension\Attribute\AsTwigTest;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

/**
 * Abstract class for extension using the new PHP 8 attributes to define filters, functions, and tests.
 *
 * @author Jérôme Tamarelle <jerome@tamarelle.net>
 */
abstract class Extension extends AbstractExtension
{
    public function getFilters(): \Generator
    {
        $reflectionClass = new \ReflectionClass($this);
        foreach ($reflectionClass->getMethods() as $method) {
            foreach ($method->getAttributes(AsTwigFilter::class) as $attribute) {
                $attribute = $attribute->newInstance();
                $options = $attribute->options;
                if (!\array_key_exists('needs_environment', $options)) {
                    $param = $method->getParameters()[0] ?? null;
                    $options['needs_environment'] = $param && 'env' === $param->getName() && Environment::class === $param->getType()->getName();
                }
                $firstParam = $options['needs_environment'] ? 1 : 0;
                if (!\array_key_exists('needs_context', $options)) {
                    $param = $method->getParameters()[$firstParam] ?? null;
                    $options['needs_context'] = $param && 'context' === $param->getName() && 'array' === $param->getType()->getName();
                }
                $firstParam += $options['needs_context'] ? 1 : 0;
                if (!\array_key_exists('is_variadic', $options)) {
                    $param = $method->getParameters()[$firstParam] ?? null;
                    $options['is_variadic'] = $param && $param->isVariadic();
                }

                yield new TwigFilter($attribute->name ?? $method->getName(), [$this, $method->getName()], $options);
            }
        }
    }

    public function getFunctions(): \Generator
    {
        $reflectionClass = new \ReflectionClass($this);
        foreach ($reflectionClass->getMethods() as $method) {
            foreach ($method->getAttributes(AsTwigFunction::class) as $attribute) {
                $attribute = $attribute->newInstance();
                $options = $attribute->options;
                if (!\array_key_exists('needs_environment', $options)) {
                    $param = $method->getParameters()[0] ?? null;
                    $options['needs_environment'] = $param && 'env' === $param->getName() && Environment::class === $param->getType()->getName();
                }
                $firstParam = $options['needs_environment'] ? 1 : 0;
                if (!\array_key_exists('needs_context', $options)) {
                    $param = $method->getParameters()[$firstParam] ?? null;
                    $options['needs_context'] = $param && 'context' === $param->getName() && 'array' === $param->getType()->getName();
                }
                $firstParam += $options['needs_context'] ? 1 : 0;
                if (!\array_key_exists('is_variadic', $options)) {
                    $param = $method->getParameters()[$firstParam] ?? null;
                    $options['is_variadic'] = $param && $param->isVariadic();
                }

                yield new TwigFunction($attribute->name ?? $method->getName(), [$this, $method->getName()], $options);
            }
        }
    }

    public function getTests(): \Generator
    {
        $reflectionClass = new \ReflectionClass($this);
        foreach ($reflectionClass->getMethods() as $method) {
            foreach ($method->getAttributes(AsTwigTest::class) as $attribute) {
                $attribute = $attribute->newInstance();
                $options = $attribute->options;

                if (!\array_key_exists('is_variadic', $options)) {
                    $param = $method->getParameters()[0] ?? null;
                    $options['is_variadic'] = $param && $param->isVariadic();
                }

                yield new TwigTest($attribute->name ?? $method->getName(), [$this, $method->getName()], $options);
            }
        }
    }
}
