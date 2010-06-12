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
 * Represents a set node.
 *
 * @package    twig
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
class Twig_Node_Set extends Twig_Node
{
    public function __construct($capture, Twig_NodeInterface $names, Twig_NodeInterface $values, $lineno, $tag = null)
    {
        parent::__construct(array('names' => $names, 'values' => $values), array('capture' => $capture), $lineno, $tag);
    }

    /**
     * Compiles the node to PHP.
     *
     * @param Twig_Compiler A Twig_Compiler instance
     */
    public function compile($compiler)
    {
        $compiler->addDebugInfo($this);

        if (count($this->names) > 1) {
            $compiler->write('list(');
            foreach ($this->names as $idx => $node) {
                if ($idx) {
                    $compiler->raw(', ');
                }

                $compiler->subcompile($node);
            }
            $compiler->raw(')');
        } else {
            if ($this['capture']) {
                $compiler
                    ->write("ob_start();\n")
                    ->subcompile($this->values)
                ;
            }

            $compiler->subcompile($this->names, false);

            if ($this['capture']) {
                $compiler->raw(" = ob_get_clean()");
            }
        }

        if (!$this['capture']) {
            $compiler->raw(' = ');

            if (count($this->names) > 1) {
                $compiler->write('array(');
                foreach ($this->values as $idx => $value) {
                    if ($idx) {
                        $compiler->raw(', ');
                    }

                    $compiler->subcompile($value);
                }
                $compiler->raw(')');
            } else {
                $compiler->subcompile($this->values);
            }
        }

        $compiler->raw(";\n");
    }
}
