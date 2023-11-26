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

        /**
         * @var array{is_safe?:array|null, is_safe_callback?:callable|null, pre_escape?:string|null, preserves_safety?:array|null, deprecated?:bool|string, alternative?:string}
         */
        public array $options = [],
    ) {
    }
}
