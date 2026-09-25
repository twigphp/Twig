<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Tests\Node\Expression\Filter;

use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\Filter\EscapeFilter;
use Twig\Node\Nodes;
use Twig\Test\NodeTestCase;

class EscapeTest extends NodeTestCase
{
    public static function provideTests(): iterable
    {
        $env = new Environment(new ArrayLoader());
        $arguments = new Nodes([new ConstantExpression('html', 1)]);

        $node = new EscapeFilter(new ConstantExpression('foo', 1), $env->getFilter('escape'), $arguments, 1);
        yield 'not flagged by the escaper node visitor' => [$node, '$this->env->getRuntime(\'Twig\Runtime\EscaperRuntime\')->escape("foo", "html")', $env];

        $node = new EscapeFilter(new ConstantExpression('foo', 1), $env->getFilter('escape'), $arguments, 1);
        $node->setAttribute('template_escaper', true);
        yield 'flagged by the escaper node visitor' => [$node, '$this->escaper->escape("foo", "html")', $env];
    }
}
