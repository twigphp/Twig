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

use Twig\Node\Expression\TestExpression;

/**
 * Represents a template test.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @see https://twig.symfony.com/doc/templates.html#test-operator
 */
final class TwigTest
{
    /**
     * @var string
     */
    private $name;
    /**
     * @var callable|null
     */
    private $callable;
    /**
     * @var array
     */
    private $options;
    /**
     * @var array
     */
    private $arguments = [];

    /**
     * @param string $name
     * @param callable|null $callable A callable implementing the test. If null, you need to overwrite the "node_class" option to customize compilation.
     * @param array $options
     */
    public function __construct(string $name, $callable = null, array $options = [])
    {
        $this->name = $name;
        $this->callable = $callable;
        $this->options = array_merge([
            'is_variadic' => false,
            'node_class' => TestExpression::class,
            'deprecated' => false,
            'alternative' => null,
            'one_mandatory_argument' => false,
        ], $options);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Returns the callable to execute for this test.
     *
     * @return callable|null
     */
    public function getCallable()
    {
        return $this->callable;
    }

    /**
     * @return string
     */
    public function getNodeClass(): string
    {
        return $this->options['node_class'];
    }

    /**
     * @param array $arguments
     */
    public function setArguments(array $arguments): void
    {
        $this->arguments = $arguments;
    }

    /**
     * @return array
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * @return bool
     */
    public function isVariadic(): bool
    {
        return (bool) $this->options['is_variadic'];
    }

    /**
     * @return bool
     */
    public function isDeprecated(): bool
    {
        return (bool) $this->options['deprecated'];
    }

    /**
     * @return string
     */
    public function getDeprecatedVersion(): string
    {
        return \is_bool($this->options['deprecated']) ? '' : $this->options['deprecated'];
    }

    /**
     * @return string|null
     */
    public function getAlternative(): ?string
    {
        return $this->options['alternative'];
    }

    /**
     * @return bool
     */
    public function hasOneMandatoryArgument(): bool
    {
        return (bool) $this->options['one_mandatory_argument'];
    }
}
