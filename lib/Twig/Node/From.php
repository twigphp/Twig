<?php

/*
 * This file is part of Twig.
 *
 * (c) 2010 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Represents a from node.
 *
 * @package    twig
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 */
class Twig_Node_From extends Twig_Node_Import
{
    public function __construct(Twig_Node_Expression $expr, array $imports, $lineno, $tag = null)
    {
        parent::__construct($expr, new Twig_Node_Expression_AssignName('_imported_'.rand(10000, 99999), $lineno), $lineno, $tag);

        $this->setAttribute('imports', $imports);
    }

    /**
     * Compiles the node to PHP.
     *
     * @param Twig_Compiler A Twig_Compiler instance
     */
    public function compile($compiler)
    {
        parent::compile($compiler);

        foreach ($this->getAttribute('imports') as $name => $alias) {
            $compiler
                ->write('$context[')
                ->repr('fn_'.$alias)
                ->raw('] = new Twig_Function(')
                ->subcompile($this->getNode('var'))
                ->raw(', ')
                ->repr($name)
                ->raw(");\n")
            ;
        }
    }
}
