<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Node\Expression;

use Twig\Attribute\FirstClassTwigCallableReady;
use Twig\Node\EmptyNode;
use Twig\Node\Node;
use Twig\TwigTest;

class TestExpression extends CallExpression implements ReturnBoolInterface
{
    #[FirstClassTwigCallableReady]
    public function __construct(AbstractExpression $node, TwigTest $test, ?Node $arguments, int $lineno)
    {
        parent::__construct(['node' => $node, 'arguments' => $arguments ?: new EmptyNode()], ['name' => $test->getName(), 'type' => 'test', 'twig_callable' => $test], $lineno);
    }
}
