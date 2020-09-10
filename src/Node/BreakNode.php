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

final class BreakNode extends Node
{
    /**
     * @var int
     */
    private $target;

    public function __construct(int $target, int $linenumber, string $tag)
    {
        parent::__construct([], ['target' => $target], $linenumber, $tag);

        $this->target = $target;
    }

    public function setTarget(int $target): void
    {
        $this->target = $target;
    }

    public function compile(Compiler $compiler): void
    {
        $compiler
            ->addDebugInfo($this)
            ->write($this->target > 1 ? sprintf("break %d;\n", $this->target) : "break;\n")
        ;
    }
}
