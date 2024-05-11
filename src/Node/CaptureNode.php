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
    public function __construct(Node $body, int $lineno, ?string $tag = null)
    {
        parent::__construct(['body' => $body], ['raw' => false], $lineno, $tag);
    }

    public function compile(Compiler $compiler): void
    {
        if ($this->getAttribute('raw')) {
            $compiler->raw("implode('', iterator_to_array(");
        } else {
            $compiler->raw("('' === \$tmp = implode('', iterator_to_array(");
        }
        $compiler
            ->raw("(function () use (&\$context, \$macros, \$blocks) {\n")
            ->indent()
            ->subcompile($this->getNode('body'))
            ->write("return; yield '';\n")
            ->outdent()
            ->write('})(), false))')
        ;
        if (!$this->getAttribute('raw')) {
            $compiler->raw(") ? '' : new Markup(\$tmp, \$this->env->getCharset());");
        }
    }
}
