<?php

/*
 * This file is part of Twig.
 *
 * (c) 2009 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Represents a macro node.
 *
 * @package    twig
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
class Twig_Node_Macro extends Twig_Node
{
    public function __construct($name, Twig_NodeInterface $body, Twig_NodeInterface $arguments, $lineno, $tag = null)
    {
        parent::__construct(array('body' => $body, 'arguments' => $arguments), array('name' => $name), $lineno, $tag);
    }

    /**
     * Compiles the node to PHP.
     *
     * @param Twig_Compiler A Twig_Compiler instance
     */
    public function compile($compiler)
    {
        $arguments = array();
        foreach ($this->arguments as $argument) {
            $arguments[] = '$'.$argument['name'].' = null';
        }

        $compiler
            ->addDebugInfo($this)
            ->write(sprintf("public function get%s(%s)\n", $this['name'], implode(', ', $arguments)), "{\n")
            ->indent()
            ->write("\$context = array(\n")
            ->indent()
        ;

        foreach ($this->arguments as $argument) {
            $compiler
                ->write('')
                ->string($argument['name'])
                ->raw(' => $'.$argument['name'])
                ->raw(",\n")
            ;
        }

        $compiler
            ->outdent()
            ->write(");\n\n")
            ->subcompile($this->body)
            ->outdent()
            ->write("}\n\n")
        ;
    }
}
