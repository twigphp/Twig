<?php

/*
 * This file is part of Twig.
 *
 * (c) 2009 Fabien Potencier
 * (c) 2013 Berny Cantos
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Twig_Node_Extract extends Twig_Node
{
    public function __construct(array $nodes, $lineno)
    {
        parent::__construct($nodes, array(), $lineno);
    }

    public function compile(Twig_Compiler $compiler)
    {
        $compiler->write("\$context = array_merge(\$context")->indent();
        foreach ($this->nodes as $node) {
            $compiler->raw(", \n")->write('(array) ')->subcompile($node);
        }
        $compiler->raw("\n")->outdent()->write(");\n");
    }
}
