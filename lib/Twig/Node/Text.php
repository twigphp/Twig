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

use Twig\Compiler;
use Twig\Node\Node;
use Twig\Node\NodeOutputInterface;

/**
 * Represents a text node.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Twig_Node_Text extends Node implements NodeOutputInterface
{
    public function __construct($data, $lineno)
    {
        parent::__construct([], ['data' => $data], $lineno);
    }

    public function compile(Compiler $compiler)
    {
        $compiler
            ->addDebugInfo($this)
            ->write('echo ')
            ->string($this->getAttribute('data'))
            ->raw(";\n")
        ;
    }
}

class_alias('Twig_Node_Text', 'Twig\Node\TextNode', false);
