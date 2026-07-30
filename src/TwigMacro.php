<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig;

use Twig\Error\RuntimeError;
use Twig\Node\Expression\Variable\LocalVariable;

/**
 * Represents a compiled macro: its body compiled into a closure and the
 * signature declared in the template.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @internal
 */
final class TwigMacro
{
    /**
     * @var array<string, int>
     */
    private array $argumentIndexes = [];

    /**
     * @var array<string, true>
     */
    private array $requiredNames = [];

    /**
     * @var array<string, string>
     */
    private array $renamedArguments = [];

    private int $requiredCount = 0;

    /**
     * @param \Closure(mixed...): (string|Markup) $body      The compiled macro body, invoked with the bound arguments in definition order
     * @param array<string, bool>                 $arguments The declared argument names mapped to whether they have a default value, in definition order
     */
    public function __construct(
        private string $name,
        private \Closure $body,
        private array $arguments = [],
        private bool $variadic = false,
    ) {
        $i = 0;
        foreach ($arguments as $argName => $hasDefault) {
            $this->argumentIndexes[$argName] = $i;
            if (\in_array($argName, LocalVariable::RESERVED_NAMES, true)) {
                $this->renamedArguments[$argName] = "\u{035C}".$argName;
            }
            if (!$hasDefault) {
                $this->requiredCount = $i + 1;
                $this->requiredNames[$argName] = true;
            }
            ++$i;
        }
    }

    /**
     * @param array<int|string, mixed> $arguments Positional arguments keyed by their integer position and named arguments keyed by their name
     */
    public function call(array $arguments, Source $source, int $lineno): string|Markup
    {
        if (array_is_list($arguments)) {
            $count = \count($arguments);
            $this->validateRequiredArguments($arguments, $count, $source, $lineno);
            if (!$this->variadic && $count > \count($this->arguments)) {
                throw new RuntimeError(\sprintf('Too many arguments for macro "%s".', $this->name), $lineno, $source);
            }

            return ($this->body)(...$arguments);
        }

        $positionalCount = 0;
        $sawNamed = false;
        $duplicate = null;
        $unknown = null;
        foreach ($arguments as $key => $value) {
            if (\is_int($key)) {
                if ($sawNamed) {
                    throw new RuntimeError(\sprintf('Positional arguments cannot be used after named arguments for macro "%s".', $this->name), $lineno, $source);
                }
                ++$positionalCount;
            } else {
                $sawNamed = true;
                if (null === $i = $this->argumentIndexes[$key] ?? null) {
                    $unknown ??= $key;
                } elseif (null === $duplicate && $i < $positionalCount) {
                    $duplicate = $key;
                }
            }
        }

        if (null !== $duplicate) {
            throw new RuntimeError(\sprintf('Argument "%s" is defined twice for macro "%s".', $duplicate, $this->name), $lineno, $source);
        }

        $this->validateRequiredArguments($arguments, $positionalCount, $source, $lineno);
        if (!$this->variadic) {
            if ($positionalCount > \count($this->arguments)) {
                throw new RuntimeError(\sprintf('Too many arguments for macro "%s".', $this->name), $lineno, $source);
            }
            if (null !== $unknown) {
                throw new RuntimeError(\sprintf('Unknown argument "%s" for macro "%s".', $unknown, $this->name), $lineno, $source);
            }
        }

        foreach ($this->renamedArguments as $name => $parameterName) {
            if (\array_key_exists($name, $arguments)) {
                $arguments[$parameterName] = $arguments[$name];
                unset($arguments[$name]);
            }
        }

        return ($this->body)(...$arguments);
    }

    /**
     * @param array<int|string, mixed> $arguments
     */
    private function validateRequiredArguments(array $arguments, int $positionalCount, Source $source, int $lineno): void
    {
        if ($positionalCount >= $this->requiredCount) {
            return;
        }

        foreach ($this->requiredNames as $name => $required) {
            if ($this->argumentIndexes[$name] < $positionalCount || \array_key_exists($name, $arguments)) {
                continue;
            }

            throw new RuntimeError(\sprintf('Value for argument "%s" is required for macro "%s".', $name, $this->name), $lineno, $source);
        }
    }
}
