<?php

namespace Twig\Extension\Attribute;

use Twig\TwigFilter;

/**
 * Registers a method as template filter.
 *
 * @see TwigFilter
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class AsTwigFilter
{
    public function __construct(
        /**
         * The name of the filter in Twig (defaults to the method name).
         *
         * @var non-empty-string|null $name
         */
        public ?string $name = null,
        public bool $isSafe = false,
        public ?string $isSafeCallback = null,
        public ?string $preEscape = null,
        public ?array $preservesSafety = null,
        public bool|string $deprecated = false,
        public ?string $alternative = null,
    ) {
    }
}
