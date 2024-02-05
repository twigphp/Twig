<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Node;

use Twig\Compiler;

/**
 * Represents a node for which we need to capture the output.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class CaptureNode extends Node
{
    public function __construct(Node $body, int $lineno, string $tag = null)
    {
        parent::__construct(['body' => $body], ['raw' => false, 'with_blocks' => false], $lineno, $tag);
    }

    public function compile(Compiler $compiler): void
    {
        if ($this->getAttribute('raw')) {
            $compiler->raw("implode('', iterator_to_array(");
        } else {
            $compiler->raw("('' === \$tmp = implode('', iterator_to_array(");
        }
        if ($this->getAttribute('with_blocks')) {
            $compiler->raw("(function () use (&\$context, \$macros, \$blocks) {\n");
        } else {
            $compiler->raw("(function () use (&\$context, \$macros) {\n");
        }
        $compiler
            ->indent()
            ->subcompile($this->getNode('body'))
            ->outdent()
            ->write("})() ?? new \EmptyIterator()))")
        ;
        if (!$this->getAttribute('raw')) {
            $compiler->raw(") ? '' : new Markup(\$tmp, \$this->env->getCharset())");
        }
        $compiler->raw(';');
    }
}
