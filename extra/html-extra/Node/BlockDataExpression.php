<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Extra\Html\Node;

use Twig\Compiler;
use Twig\Extra\Html\HtmlExtension;
use Twig\Node\CoercesChildrenToStringInterface;
use Twig\Node\Expression\AbstractExpression;
use Twig\Node\Node;

/**
 * Represents a block_data() call node.
 *
 * Returns the structured data exported by a block via block_export(),
 * instead of the rendered string returned by block().
 */
class BlockDataExpression extends AbstractExpression implements CoercesChildrenToStringInterface
{
    public function __construct(Node $name, ?Node $template, int $lineno)
    {
        $nodes = ['name' => $name];
        if (null !== $template) {
            $nodes['template'] = $template;
        }

        parent::__construct($nodes, [], $lineno);
    }

    public function compile(Compiler $compiler): void
    {
        $compiler->raw(\sprintf('$this->extensions[\'%s\']->renderBlockData(', HtmlExtension::class));

        if (!$this->hasNode('template')) {
            $compiler->raw('$this');
        } else {
            $compiler
                ->raw('$this->load(')
                ->subcompile($this->getNode('template'))
                ->raw(', ')
                ->repr($this->getTemplateLine())
                ->raw(')')
            ;
        }

        $compiler
            ->raw(', ')
            ->subcompile($this->getNode('name'))
            ->raw(', $context')
        ;

        if (!$this->hasNode('template')) {
            $compiler->raw(', $blocks');
        }

        $compiler->raw(')');
    }

    public function getStringCoercedChildNames(): array
    {
        return $this->hasNode('template') ? ['name', 'template'] : ['name'];
    }
}
