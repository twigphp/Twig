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

use Twig\Compiler;
use Twig\Node\Node;

/**
 * Represents a parent node.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class ParentExpression extends AbstractExpression
{
    public function __construct(string $name, int $lineno, /* Node */ $level = null, string $tag = null)
    {
        if (\is_string($level)) {
            $tag = $level;
            $level = null;

            @trigger_error(sprintf('Passing $tag as the 3rd argument instead of 4th to the %s constructor is deprecated since Twig 3.5.', __CLASS__), \E_USER_DEPRECATED);
        }

        if (null !== $level && !$level instanceof Node) {
            throw new \TypeError(sprintf('Argument 3 passed to "%s()" must be an instance of "%s" or null, "%s" given.', __METHOD__, Node::class, \is_object($level) ? \get_class($level) : \gettype($level)));
        }

        $nodes = [];
        if (null !== $level) {
            $nodes['level'] = $level;
        }

        parent::__construct($nodes, ['output' => false, 'name' => $name], $lineno, $tag);
    }

    public function compile(Compiler $compiler): void
    {
        if ($this->getAttribute('output')) {
            $compiler
                ->addDebugInfo($this)
                ->write('$this->displayParentBlock(')
                ->string($this->getAttribute('name'))
                ->raw(', $context, $blocks')
            ;
            $this->compileLevel($compiler);
            $compiler->raw(");\n");
        } else {
            $compiler
                ->raw('$this->renderParentBlock(')
                ->string($this->getAttribute('name'))
                ->raw(', $context, $blocks')
            ;
            $this->compileLevel($compiler);
            $compiler->raw(')');
        }
    }

    private function compileLevel(Compiler $compiler): void
    {
        if (!$this->hasNode('level')) {
            return;
        }

        $compiler
            ->raw(', ')
            ->subcompile($this->getNode('level'))
        ;
    }
}
