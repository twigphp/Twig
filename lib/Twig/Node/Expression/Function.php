<?php

/*
 * This file is part of Twig.
 *
 * (c) 2010 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Twig_Node_Expression_Function extends Twig_Node_Expression_Call
{
    public function __construct($name, Twig_Node $arguments, $lineno)
    {
        parent::__construct(array('arguments' => $arguments), array('name' => $name), $lineno);
    }

    public function compile(Twig_Compiler $compiler)
    {
        $name = $this->getAttribute('name');
        $function = $compiler->getEnvironment()->getFunction($name);

        $this->setAttribute('name', $name);
        $this->setAttribute('type', 'function');
        $this->setAttribute('needs_environment', $function->needsEnvironment());
        $this->setAttribute('needs_context', $function->needsContext());
        $this->setAttribute('arguments', $function->getArguments());
        $this->setAttribute('callable', $function->getCallable());
        $this->setAttribute('is_variadic', $function->isVariadic());

        $this->compileCallable($compiler);
    }
}
