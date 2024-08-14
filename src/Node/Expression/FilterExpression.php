<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 * (c) Armin Ronacher
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Node\Expression;

use Twig\Attribute\FirstClassTwigCallableReady;
use Twig\Compiler;
use Twig\Node\Node;
use Twig\TwigFilter;

class FilterExpression extends CallExpression
{
    #[FirstClassTwigCallableReady]
    public function __construct(Node $node, TwigFilter $filter, Node $arguments, int $lineno, ?string $tag = null)
    {
        parent::__construct(['node' => $node, 'arguments' => $arguments], ['name' => $filter->getName(), 'type' => 'filter', 'twig_callable' => $filter], $lineno, $tag);
    }

    public function compile(Compiler $compiler): void
    {
        $this->compileCallable($compiler);
    }
}
