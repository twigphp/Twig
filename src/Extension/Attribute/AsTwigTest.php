<?php

namespace Twig\Extension\Attribute;

use Twig\TwigTest;

/**
 * Registers a method as template test.
 *
 * @see TwigTest
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class AsTwigTest
{
    public function __construct(
        /**
         * The name of the filter in Twig.
         *
         * @var non-empty-string $name
         */
        public string $name,
        public bool|string $deprecated = false,
        public ?string $alternative = null,
    ) {
    }
}
