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

        /**
         * @var array{needs_environment?:bool, needs_context?:bool, is_variadic?:bool, is_safe?:array|null, is_safe_callback?:callable|null, node_class?:class-string, deprecated?:bool|string, alternative?:string}
         */
        public array $options = [],
    ) {
    }
}
