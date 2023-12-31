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
        if ($this->getAttribute('with_blocks')) {
            $compiler->raw("(function () use (&\$context, \$macros, \$blocks) {\n");
        } else {
            $compiler->raw("(function () use (&\$context, \$macros) {\n");
        }
        $compiler->indent();
        if ($compiler->getEnvironment()->isDebug()) {
            $compiler->write("ob_start();\n");
        } else {
            $compiler->write("ob_start(function () { return ''; });\n");
        }
        $compiler
            ->write("try {\n")
            ->indent()
            ->subcompile($this->getNode('body'))
            ->raw("\n")
        ;
        if ($this->getAttribute('raw')) {
            $compiler->write("return ob_get_contents();\n");
        } else {
            $compiler->write("return ('' === \$tmp = ob_get_contents()) ? '' : new Markup(\$tmp, \$this->env->getCharset());\n");
        }
        $compiler
            ->outdent()
            ->write("} finally {\n")
            ->indent()
            ->write("ob_end_clean();\n")
            ->outdent()
            ->write("}\n")
            ->outdent()
            ->write('})();')
        ;
    }
}
