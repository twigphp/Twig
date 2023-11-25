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

use Twig\DeprecatedCallableInfo;
use Twig\TwigTest;

/**
 * Registers a method as template test.
 *
 * The first argument is the value to test and the other arguments are the
 * arguments passed to the test in the template.
 *
 *     #[AsTwigTest(name: 'foo')]
 *     public function fooTest($value, $arg1 = null) { ... }
 *
 *     {% if value is foo(arg1) %}
 *
 * @see TwigTest
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class AsTwigTest
{
    /**
     * @param non-empty-string $name The name of the test in Twig.
     * @param bool|null $needsCharset Whether the test needs the charset passed as the first argument.
     * @param bool|null $needsEnvironment Whether the test needs the environment passed as the first argument, or after the charset.
     * @param bool|null $needsContext Whether the test needs the context array passed as the first argument, or after the charset and the environment.
     * @param DeprecatedCallableInfo|null $deprecationInfo Information about the deprecation
     */
    public function __construct(
        public string $name,
        public ?bool $needsCharset = null,
        public ?bool $needsEnvironment = null,
        public ?bool $needsContext = null,
        public ?DeprecatedCallableInfo $deprecationInfo = null,
    ) {
    }
}
