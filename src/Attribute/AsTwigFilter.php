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
use Twig\TwigFilter;

/**
 * Registers a method as template filter.
 *
 * If the first argument of the method has Twig\Environment type-hint, the filter will receive the current environment.
 * If the next argument of the method is named $context and has array type-hint, the filter will receive the current context.
 * Additional arguments of the method come from the filter call.
 *
 *     #[AsTwigFilter('foo')]
 *     function fooFilter(Environment $env, array $context, $string, $arg1 = null, ...) { ... }
 *
 * @see TwigFilter
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class AsTwigFilter
{
    public function __construct(
        /**
         * The name of the filter in Twig.
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
         * Function called at compilation time to determine if the filter is safe.
         *
         * @var callable(Node):bool $isSafeCallback
         */
        public ?string $isSafeCallback = null,

        /**
         * Some filters may need to work on input that is already escaped or safe, for
         * example when adding (safe) HTML tags to originally unsafe output. In such a
         * case, set preEscape to an escape format to escape the input data before it
         * is run through the filter.
         */
        public ?string $preEscape = null,

        /**
         * Preserves the safety of the value that the filter is applied to.
         */
        public ?array $preservesSafety = null,

        /**
         * Set to true if the filter is deprecated.
         */
        public bool|string $deprecated = false,

        /**
         * The alternative filter name to suggest when the deprecated filter is called.
         */
        public ?string $alternative = null,
    ) {
    }
}
