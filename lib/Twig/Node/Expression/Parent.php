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
 * Represents a parent node.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Martin Hasoň <martin.hason@gmail.com>
 */
class Twig_Node_Expression_Parent extends Twig_Node_Expression_GeneralCall
{
    public function __construct($name, $lineno, $tag = null, Twig_Node $arguments = null)
    {
        $arguments = null === $arguments ? new Twig_Node() : $arguments;
        parent::__construct(array('arguments' => $arguments), array('output' => false, 'name' => $name), $lineno, $tag);
    }

    public function compile(Twig_Compiler $compiler)
    {
        $output = $this->getAttribute('output');
        if ($output) {
            $compiler->addDebugInfo($this)->write('$this->displayParentBlock(');
        } else {
            $compiler->raw('$this->renderParentBlock(');
        }

        $compiler->string($this->getAttribute('name'))->raw(', ');

        $arguments = $this->getArgumentsForCallable(array($this, 'parentFunction'), $this->getNode('arguments'), 'function', 'parent', false, true);

        if (isset($arguments['variables'])) {
            $compiler->raw('array_merge(');
        }

        if (isset($arguments['with_context'])) {
            $compiler->raw('(')->subcompile($arguments['with_context'])->raw(') ? $context : array()');
        } else {
            $compiler->raw('$context');
        }

        if (isset($arguments['variables'])) {
            $compiler->raw(', ')->subcompile($arguments['variables'])->raw(')');
        }

        $compiler->raw($output ? ", \$blocks);\n" : ', $blocks)');
    }

    /**
     * This method is used to obtain reflection of arguments.
     */
    protected function parentFunction($variables = array(), $withContext = true)
    {
    }
}
