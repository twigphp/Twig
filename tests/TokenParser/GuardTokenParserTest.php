<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Tests\TokenParser;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Error\SyntaxError;
use Twig\Loader\ArrayLoader;
use Twig\Parser;
use Twig\Source;

class GuardTokenParserTest extends TestCase
{
    public function testUndefinedHandlers()
    {
        $this->expectNotToPerformAssertions();

        $env = new Environment(new ArrayLoader(), ['cache' => false, 'autoescape' => false]);
        $env->registerUndefinedFunctionCallback(fn ($name) => throw new SyntaxError('boom.'));
        (new Parser($env))->parse($env->tokenize(new Source('{% guard function boom %}{% endguard %}', '')));
    }
}
