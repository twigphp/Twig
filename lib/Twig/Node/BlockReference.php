<?php

/*
 * This file is part of Twig.
 *
 * (c) 2009 Fabien Potencier
 * (c) 2009 Armin Ronacher
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Represents a block call node.
 *
 * @package    twig
 * @author     Fabien Potencier <fabien@symfony.com>
 */
class Twig_Node_BlockReference extends Twig_Node implements Twig_NodeOutputInterface
{
    public function __construct($name, $lineno, $tag = null)
    {
        // hack to be BC
        if ($name instanceof Twig_NodeInterface) {
            parent::__construct(array('name' => $name), array(), $lineno, $tag);
        } else {
            parent::__construct(array(), array('name' => $name), $lineno, $tag);
        }
    }

    /**
     * Compiles the node to PHP.
     *
     * @param Twig_Compiler A Twig_Compiler instance
     */
    public function compile(Twig_Compiler $compiler)
    {
        if ($this->hasNode('name')) {
            $compiler
                ->addDebugInfo($this)
                ->write("\$this->displayBlock(")
                ->subcompile($this->getNode('name'))
                ->raw(", \$context, \$blocks);\n")
            ;
        } else {
            $compiler
                ->addDebugInfo($this)
                ->write(sprintf("\$this->displayBlock('%s', \$context, \$blocks);\n", $this->getAttribute('name')))
            ;
        }
    }
}
