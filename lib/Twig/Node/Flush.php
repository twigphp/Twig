<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Twig\Compiler;
use Twig\Node\Node;

/**
 * Represents a flush node.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Twig_Node_Flush extends Node
{
    public function __construct($lineno, $tag)
    {
        parent::__construct([], [], $lineno, $tag);
    }

    public function compile(Compiler $compiler)
    {
        $compiler
            ->addDebugInfo($this)
            ->write("flush();\n")
        ;
    }
}

class_alias('Twig_Node_Flush', 'Twig\Node\FlushNode', false);
