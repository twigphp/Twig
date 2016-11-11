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
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Martin Hasoň <martin.hason@gmail.com>
 */
class Twig_Node_Expression_BlockReference extends Twig_Node_Expression_GeneralCall
{
    public function __construct(Twig_NodeInterface $name, $asString = false, $lineno = -1, $tag = null, Twig_Node $arguments = null)
    {
        $arguments = null === $arguments ? new Twig_Node(array('name' => $name)) : $arguments;
        parent::__construct(array('name' => $name, 'arguments' => $arguments), array('as_string' => $asString, 'output' => false), $lineno, $tag);
    }

    public function compile(Twig_Compiler $compiler)
    {
        if ($this->getAttribute('as_string')) {
            $compiler->raw('(string) ');
        }

        $output = $this->getAttribute('output');
        if ($output) {
            $compiler->addDebugInfo($this)->write('$this->displayBlock(');
        } else {
            $compiler->raw('$this->renderBlock(');
        }

        $compiler->subcompile($this->getNode('name'))->raw(', ');

        $arguments = $this->getArgumentsForCallable(array($this, 'blockFunction'), $this->getNode('arguments'), 'function', 'block', false, true);

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
    protected function blockFunction($name, $variables = array(), $withContext = true)
    {
    }
}
