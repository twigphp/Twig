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
 * Define Twig filters, functions, and tests with PHP attributes.
 *
 * @author Jérôme Tamarelle <jerome@tamarelle.net>
 */
final class AttributeExtension extends AbstractExtension
{
    private array $filters;
    private array $functions;
    private array $tests;

    public function __construct(
        /**
         * A list of objects or class names defining filters, functions, and tests using PHP attributes.
         * When passing a class name, it must be available in runtimes.
         *
         * @var iterable<object|class-string>
         */
        private iterable $objectsOrClasses,
    ) {
    }

    public function getFilters(): array
    {
        if (!isset($this->filters)) {
            $this->initFromAttributes();
        }

        return $this->filters;
    }

    public function getFunctions(): array
    {
        if (!isset($this->functions)) {
            $this->initFromAttributes();
        }

        return $this->functions;
    }

    public function getTests(): array
    {
        if (!isset($this->tests)) {
            $this->initFromAttributes();
        }

        return $this->tests;
    }

    private function initFromAttributes()
    {
        $filters = $functions = $tests = [];

        foreach ($this->objectsOrClasses as $objectOrClass) {
            try {
                $reflectionClass = new \ReflectionClass($objectOrClass);
            } catch (\ReflectionException $e) {
                throw new \LogicException(sprintf('"%s" class requires a list of objects or class name, "%s" given.', __CLASS__, get_debug_type($objectOrClass)), 0, $e);
            }

            foreach ($reflectionClass->getMethods() as $method) {
                // Filters
                foreach ($method->getAttributes(AsTwigFilter::class) as $attribute) {
                    $attribute = $attribute->newInstance();

                    $name = $attribute->name ?? $method->getName();
                    if (isset($filters[$name])) {
                        throw new \LogicException(sprintf('Multiple definitions of the "%s" filter.', $name));
                    }

                    $parameters = $method->getParameters();
                    $needsEnvironment = isset($parameters[0]) && 'env' === $parameters[0]->getName() && Environment::class === $parameters[0]->getType()->getName();
                    $firstParam = $needsEnvironment ? 1 : 0;
                    $needsContext = isset($parameters[$firstParam]) && 'context' === $parameters[$firstParam]->getName() && 'array' === $parameters[$firstParam]->getType()->getName();
                    $firstParam += $needsContext ? 1 : 0;
                    $isVariadic = isset($parameters[$firstParam]) && $parameters[$firstParam]->isVariadic();

                    $filters[$name] = new TwigFilter($name, [$objectOrClass, $method->getName()], [
                        'needs_environment' => $needsEnvironment,
                        'needs_context' => $needsContext,
                        'is_variadic' => $isVariadic,
                        'is_safe' => $attribute->isSafe,
                        'is_safe_callback' => $attribute->isSafeCallback,
                        'pre_escape' => $attribute->preEscape,
                        'preserves_safety' => $attribute->preservesSafety,
                        'deprecated' => $attribute->deprecated,
                        'alternative' => $attribute->alternative,
                    ]);
                }

                // Functions
                foreach ($method->getAttributes(AsTwigFunction::class) as $attribute) {
                    $attribute = $attribute->newInstance();

                    $name = $attribute->name ?? $method->getName();
                    if (isset($functions[$name])) {
                        throw new \LogicException(sprintf('Multiple definitions of the "%s" function.', $name));
                    }

                    $parameters = $method->getParameters();
                    $needsEnvironment = isset($parameters[0]) && Environment::class === $parameters[0]->getType()->getName();
                    $firstParam = $needsEnvironment ? 1 : 0;
                    $needsContext = isset($parameters[$firstParam]) && 'context' === $parameters[$firstParam]->getName() && 'array' === $parameters[$firstParam]->getType()->getName();
                    $firstParam += $needsContext ? 1 : 0;
                    $isVariadic = isset($parameters[$firstParam]) && $parameters[$firstParam]->isVariadic();

                    $functions[$name] = new TwigFunction($name, [$objectOrClass, $method->getName()], [
                        'needs_environment' => $needsEnvironment,
                        'needs_context' => $needsContext,
                        'is_variadic' => $isVariadic,
                        'is_safe' => $attribute->isSafe,
                        'is_safe_callback' => $attribute->isSafeCallback,
                        'deprecated' => $attribute->deprecated,
                        'alternative' => $attribute->alternative,
                    ]);
                }

                // Tests
                foreach ($method->getAttributes(AsTwigTest::class) as $attribute) {
                    $attribute = $attribute->newInstance();

                    $name = $attribute->name ?? $method->getName();
                    if (isset($tests[$name])) {
                        throw new \LogicException(sprintf('Multiple definitions of the "%s" test.', $name));
                    }

                    $parameters = $method->getParameters();
                    $isVariadic = isset($parameters[0]) && $parameters[0]->isVariadic();

                    $tests[$name] = new TwigTest($name, [$objectOrClass, $method->getName()], [
                        'is_variadic' => $isVariadic,
                        'deprecated' => $attribute->deprecated,
                        'alternative' => $attribute->alternative,
                    ]);
                }
            }
        }

        // Assign all at the end to avoid inconsistent state in case of exception
        $this->filters = $filters;
        $this->functions = $functions;
        $this->tests = $tests;
    }
}
