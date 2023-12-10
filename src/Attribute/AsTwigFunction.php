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

use Twig\Node\Node;
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
final class AsTwigFunction
{
    public function __construct(
        /**
         * The name of the function in Twig.
         *
         * @var non-empty-string $name
         */
        public string $name,

        /**
         * List of formats in which you want the raw output to be printed unescaped.
         *
         * @var list<string>|null $isSafe
         */
        public ?array $isSafe = null,

        /**
         * Function called at compilation time to determine if the function is safe.
         *
         * @var callable(Node):bool $isSafeCallback
         */
        public ?string $isSafeCallback = null,

        /**
         * Set to true if the function is deprecated.
         */
        public bool|string $deprecated = false,

        /**
         * The alternative function name to suggest when the deprecated function is called.
         */
        public ?string $alternative = null,
    ) {
    }
}
