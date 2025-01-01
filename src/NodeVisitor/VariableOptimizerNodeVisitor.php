<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\NodeVisitor;

use Twig\Environment;
use Twig\Node\Expression\NameExpression;
use Twig\Node\ModuleNode;
use Twig\Node\Node;
use Twig\Node\TypesNode;

/**
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @internal
 */
final class VariableOptimizerNodeVisitor implements NodeVisitorInterface
{
    private array $types = [];

    public function enterNode(Node $node, Environment $env): Node
    {
        if ($node instanceof TypesNode) {
            $this->types = array_merge($this->types, $node->getAttribute('mapping'));
        }

        // A NameExpression is always defined if the variable is typed and not optional
        if ($node instanceof NameExpression && isset($this->types[$node->getAttribute('name')]) && !$this->types[$node->getAttribute('name')]['optional']) {
            $node->setAttribute('always_defined', true);
        }

        return $node;
    }

    public function leaveNode(Node $node, Environment $env): ?Node
    {
        if ($node instanceof ModuleNode) {
            $this->types = [];
        }

        return $node;
    }

    public function getPriority(): int
    {
        return 255;
    }
}
