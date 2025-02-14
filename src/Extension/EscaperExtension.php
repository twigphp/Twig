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

use Twig\FileExtensionEscapingStrategy;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\Filter\RawFilter;
use Twig\Node\Node;
use Twig\NodeVisitor\EscaperNodeVisitor;
use Twig\Runtime\EscaperRuntime;
use Twig\TokenParser\AutoEscapeTokenParser;
use Twig\TwigFilter;

final class EscaperExtension extends AbstractExtension
{
    /**
     * @param string|false|callable $defaultStrategy An escaping strategy
     *
     * @see setDefaultStrategy()
     */
    public function __construct(
        private $defaultStrategy = 'html',
    ) {
        $this->setDefaultStrategy($defaultStrategy);
    }

    public function getTokenParsers(): array
    {
        return [new AutoEscapeTokenParser()];
    }

    public function getNodeVisitors(): array
    {
        return [new EscaperNodeVisitor()];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('escape', [EscaperRuntime::class, 'escape'], ['is_safe_callback' => self::escapeFilterIsSafe(...)]),
            new TwigFilter('e', [EscaperRuntime::class, 'escape'], ['is_safe_callback' => self::escapeFilterIsSafe(...)]),
            new TwigFilter('raw', null, ['is_safe' => ['all'], 'node_class' => RawFilter::class]),
        ];
    }

    public function getLastModified(): int
    {
        return max(
            parent::getLastModified(),
            filemtime((new \ReflectionClass(EscaperRuntime::class))->getFileName()),
        );
    }

    /**
     * Sets the default strategy to use when not defined by the user.
     *
     * The strategy can be a valid PHP callback that takes the template
     * name as an argument and returns the strategy to use.
     *
     * @param string|false|callable(string $templateName): string $defaultStrategy An escaping strategy
     */
    public function setDefaultStrategy($defaultStrategy): void
    {
        if ('name' === $defaultStrategy) {
            $defaultStrategy = [FileExtensionEscapingStrategy::class, 'guess'];
        }

        $this->defaultStrategy = $defaultStrategy;
    }

    /**
     * Gets the default strategy to use when not defined by the user.
     *
     * @param string $name The template name
     *
     * @return string|false The default strategy to use for the template
     */
    public function getDefaultStrategy(string $name)
    {
        // disable string callables to avoid calling a function named html or js,
        // or any other upcoming escaping strategy
        if (!\is_string($this->defaultStrategy) && false !== $this->defaultStrategy) {
            return ($this->defaultStrategy)($name);
        }

        return $this->defaultStrategy;
    }

    /**
     * @internal
     *
     * @return array<string>
     */
    public static function escapeFilterIsSafe(Node $filterArgs)
    {
        foreach ($filterArgs as $arg) {
            if ($arg instanceof ConstantExpression) {
                return [$arg->getAttribute('value')];
            }

            return [];
        }

        return ['html'];
    }
}
