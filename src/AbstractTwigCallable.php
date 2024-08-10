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

/**
 * @author Fabien Potencier <fabien@symfony.com>
 */
abstract class AbstractTwigCallable implements TwigCallableInterface
{
    protected $options;

    private $name;
    private $callable;
    private $arguments;

    public function __construct(string $name, $callable = null, array $options = [])
    {
        $this->name = $name;
        $this->callable = $callable;
        $this->arguments = [];
        $this->options = array_merge([
            'needs_environment' => false,
            'needs_context' => false,
            'needs_charset' => false,
            'is_variadic' => false,
            'deprecated' => false,
            'deprecating_package' => '',
            'alternative' => null,
        ], $options);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCallable()
    {
        return $this->callable;
    }

    public function getNodeClass(): string
    {
        return $this->options['node_class'];
    }

    public function needsCharset(): bool
    {
        return $this->options['needs_charset'];
    }

    public function needsEnvironment(): bool
    {
        return $this->options['needs_environment'];
    }

    public function needsContext(): bool
    {
        return $this->options['needs_context'];
    }

    public function setArguments(array $arguments): void
    {
        $this->arguments = $arguments;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function isVariadic(): bool
    {
        return $this->options['is_variadic'];
    }

    public function isDeprecated(): bool
    {
        return (bool) $this->options['deprecated'];
    }

    public function getDeprecatingPackage(): string
    {
        return $this->options['deprecating_package'];
    }

    public function getDeprecatedVersion(): string
    {
        return \is_bool($this->options['deprecated']) ? '' : $this->options['deprecated'];
    }

    public function getAlternative(): ?string
    {
        return $this->options['alternative'];
    }
}
