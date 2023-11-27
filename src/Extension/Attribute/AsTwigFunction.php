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
         * The name of the function in Twig (defaults to the method name).
         *
         * @var non-empty-string|null $name
         */
        public ?string $name = null,
        public ?array $isSafe = null,
        public ?string $isSafeCallback = null,
        public bool|string $deprecated = false,
        public ?string $alternative = null,
    ) {
    }
}
