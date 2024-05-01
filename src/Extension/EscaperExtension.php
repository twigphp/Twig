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
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Node;
use Twig\NodeVisitor\EscaperNodeVisitor;
use Twig\Runtime\EscaperRuntime;
use Twig\TokenParser\AutoEscapeTokenParser;
use Twig\TwigFilter;

final class EscaperExtension extends AbstractExtension
{
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
            new TwigFilter('escape', [EscaperRuntime::class, 'escape'], ['is_safe_callback' => [self::class, 'escapeFilterIsSafe']]),
            new TwigFilter('e', [EscaperRuntime::class, 'escape'], ['is_safe_callback' => [self::class, 'escapeFilterIsSafe']]),
            new TwigFilter('raw', [self::class, 'raw'], ['is_safe' => ['all']]),
        ];
    }

    /**
     * Marks a variable as being safe.
     *
     * @param string $string A PHP variable
     *
     * @internal
     */
    public static function raw($string)
    {
        return $string;
    }

    /**
     * @internal
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
