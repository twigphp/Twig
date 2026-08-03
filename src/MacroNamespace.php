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

/**
 * @internal
 */
final class MacroNamespace
{
    /**
     * @param array<string, TwigMacro> $macros
     */
    public function __construct(
        private Template $template,
        private array $macros = [],
        private ?\Closure $importsLoader = null,
    ) {
    }

    public function has(string $name, array $context): bool
    {
        $namespace = $this;
        while (true) {
            if (isset($namespace->macros[$name])) {
                return true;
            }

            if (null === $namespace = $namespace->getParent($context)) {
                return false;
            }
        }
    }

    /**
     * @param array<int|string, mixed> $arguments
     */
    public function call(string $name, array $arguments, array $context, int $line, Source $source): string|Markup
    {
        if (null === $macro = $this->resolve($name, $context)) {
            throw new RuntimeError(\sprintf('Macro "%s" is not defined in template "%s".', $name, $this->template->getTemplateName()), $line, $source);
        }

        return $macro->call($arguments, $source, $line);
    }

    private function getDeclared(string $name): ?TwigMacro
    {
        if (!isset($this->macros[$name])) {
            return null;
        }

        $this->template->ensureSecurityChecked();
        $this->loadImports();

        return $this->macros[$name];
    }

    private function resolve(string $name, array $context): ?TwigMacro
    {
        $namespace = $this;
        while (true) {
            if (null !== $macro = $namespace->getDeclared($name)) {
                return $macro;
            }

            if (null === $namespace = $namespace->getParent($context)) {
                return null;
            }
        }
    }

    private function loadImports(): void
    {
        if (null === $loader = $this->importsLoader) {
            return;
        }

        // clear before loading so that circular imports don't recurse infinitely
        $this->importsLoader = null;
        try {
            $loader();
        } catch (\Throwable $e) {
            $this->importsLoader = $loader;

            throw $e;
        }
    }

    private function getParent(array $context): ?self
    {
        if (!$parent = $this->template->getParent($context)) {
            return null;
        }

        return $parent->unwrap()->getMacroNamespace();
    }
}
