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

use Twig\TwigTest;

/**
 * Registers a method as template test.
 *
 * If the first argument of the method has Twig\Environment type-hint, the test will receive the current environment.
 * If the next argument of the method is named $context and has array type-hint, the test will receive the current context.
 * The last argument of the method is the value to be tested, if any.
 *
 *     #[AsTwigTest('foo')]
 *     public function fooTest(Environment $env, array $context, $value, $arg1 = null) { ... }
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
