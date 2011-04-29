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
 * @author     Fabien Potencier <fabien@symfony.com>
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
    public function compile(Twig_Compiler $compiler)
    {
        $arguments = array();
        foreach ($this->getNode('arguments') as $argument) {
            $arguments[] = '$'.$argument->getAttribute('name').' = null';
        }

        $compiler
            ->addDebugInfo($this)
            ->write(sprintf("public function get%s(%s)\n", $this->getAttribute('name'), implode(', ', $arguments)), "{\n")
            ->indent()
            ->write("\$context = array_merge(\$this->env->getGlobals(), array(\n")
            ->indent()
        ;

        foreach ($this->getNode('arguments') as $argument) {
            $compiler
                ->write('')
                ->string($argument->getAttribute('name'))
                ->raw(' => $'.$argument->getAttribute('name'))
                ->raw(",\n")
            ;
        }

        $compiler
            ->outdent()
            ->write("));\n\n")
            ->write("ob_start();\n")
            ->subcompile($this->getNode('body'))
            ->raw("\n")
            ->write("return ob_get_clean();\n")
            ->outdent()
            ->write("}\n\n")
        ;
    }
}
