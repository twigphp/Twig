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
         * The name of the filter in Twig
         *
         * @var non-empty-string $name
         */
        public string $name,
        public ?array $isSafe = null,
        public ?string $isSafeCallback = null,
        public ?string $preEscape = null,
        public ?array $preservesSafety = null,
        public bool|string $deprecated = false,
        public ?string $alternative = null,
    ) {
    }
}
