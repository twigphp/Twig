<?php

/*
 * This file is part of Twig.
 *
 * (c) 2010 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Twig_Node_Expression_Test extends Twig_Node_Expression_Call
{
    public function __construct(Twig_NodeInterface $node, $name, Twig_NodeInterface $arguments = null, $lineno)
    {
        parent::__construct(array('node' => $node, 'arguments' => $arguments), array('name' => $name), $lineno);
    }

    public function compile(Twig_Compiler $compiler)
    {
        $name = $this->getAttribute('name');
        $testMap = $compiler->getEnvironment()->getTests();
        $test = $testMap[$name];

        $this->setAttribute('name', $name);
        $this->setAttribute('type', 'test');
        if ($test instanceof Twig_TestCallableInterface) {
            $this->setAttribute('callable', $test->getCallable());
        }

        $compiler->raw($test->compile());

        $this->compileArguments($compiler);
    }
}
