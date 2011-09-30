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
class Twig_Node_Expression_Name extends Twig_Node_Expression
{
    public function __construct($name, $lineno)
    {
        parent::__construct(array(), array('name' => $name, 'output' => false), $lineno);
    }

    public function compile(Twig_Compiler $compiler)
    {
        static $specialVars = array(
            '_self'    => '$this',
            '_context' => '$context',
            '_charset' => '$this->env->getCharset()',
        );

        $name = $this->getAttribute('name');

        if ($this->hasAttribute('is_defined_test')) {
            if (isset($specialVars[$name])) {
                $compiler->repr(true);
            } else {
                $compiler->raw('array_key_exists(')->repr($name)->raw(', $context)');
            }
        } elseif (isset($specialVars[$name])) {
            $compiler->raw($specialVars[$name]);
        } elseif ($this->getAttribute('output')) {
            $compiler
                ->addDebugInfo($this)
                ->write('if (isset($context[')
                ->string($name)
                ->raw("])) {\n")
                ->indent()
                ->write('echo $context[')
                ->string($name)
                ->raw("];\n")
                ->outdent()
                ->write("}\n")
            ;
        } else {
            $compiler
                ->raw('$this->getContext($context, ')
                ->string($name)
                ->raw(')')
            ;
        }
    }
}
