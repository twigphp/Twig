<?php

namespace Twig\Extension\Attribute;

use Twig\TwigFunction;

/**
 * Registers a method as template function.
 *
 * @see TwigFunction
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class AsTwigFunction
{
    public function __construct(
        /**
         * The name of the function in Twig
         *
         * @var non-empty-string $name
         */
        public string $name,
        public ?array $isSafe = null,
        public ?string $isSafeCallback = null,
        public bool|string $deprecated = false,
        public ?string $alternative = null,
    ) {
    }
}
