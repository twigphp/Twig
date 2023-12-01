<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Attribute;

use Twig\TwigFunction;

/**
 * Registers a method as template function.
 *
 * If the first argument of the method has Twig\Environment type-hint, the function will receive the current environment.
 * If the next argument of the method is named $context and has array type-hint, the function will receive the current context.
 * Additional arguments of the method come from the function call.
 *
 *     #[AsTwigFunction('foo')]
 *     function fooFunction(Environment $env, array $context, $string, $arg1 = null, ...) { ... }
 *
 * @see TwigFunction
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class AsTwigFunction
{
    public function __construct(
        /**
         * The name of the function in Twig.
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
