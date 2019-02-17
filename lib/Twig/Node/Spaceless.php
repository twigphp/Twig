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
use Twig\Node\NodeOutputInterface;

/**
 * Represents a spaceless node.
 *
 * It removes spaces between HTML tags.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Twig_Node_Spaceless extends Node implements NodeOutputInterface
{
    public function __construct(Node $body, $lineno, $tag = 'spaceless')
    {
        parent::__construct(['body' => $body], [], $lineno, $tag);
    }

    public function compile(Compiler $compiler)
    {
        $compiler
            ->addDebugInfo($this)
            ->write("ob_start();\n")
            ->subcompile($this->getNode('body'))
            ->write("echo trim(preg_replace('/>\s+</', '><', ob_get_clean()));\n")
        ;
    }
}

class_alias('Twig_Node_Spaceless', 'Twig\Node\SpacelessNode', false);
