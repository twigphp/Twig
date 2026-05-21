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
use Twig\Node\CoercesChildrenToStringInterface;
use Twig\Node\EmptyNode;
use Twig\Node\Node;
use Twig\TwigTest;

class TestExpression extends CallExpression implements ReturnBoolInterface, CoercesChildrenToStringInterface
{
    #[FirstClassTwigCallableReady]
    public function __construct(AbstractExpression $node, TwigTest $test, ?Node $arguments, int $lineno)
    {
        parent::__construct(['node' => $node, 'arguments' => $arguments ?: new EmptyNode()], ['name' => $test->getName(), 'type' => 'test', 'twig_callable' => $test], $lineno);
    }

    public function getStringCoercedChildNames(): array
    {
        $names = [];

        // the `empty` test triggers an implicit string coercion through `CoreExtension::testEmpty()`
        if ('empty' === $this->getAttribute('name')) {
            $names[] = 'node';
        }

        // a test may coerce its arguments to string (the host PHP code is opaque to Twig)
        if ($this->hasNode('arguments')) {
            $names[] = 'arguments';
        }

        return $names;
    }
}
