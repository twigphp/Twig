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
    protected $specialVars = array(
        '_self'    => '$this',
        '_context' => '$context',
        '_charset' => '$this->env->getCharset()',
    );

    public function __construct($name, $lineno)
    {
        parent::__construct(array(), array('name' => $name, 'is_defined_test' => false, 'ignore_strict_check' => false), $lineno);
    }

    public function compile(Twig_Compiler $compiler)
    {
        $name = $this->getAttribute('name');

        if ($this->getAttribute('is_defined_test')) {
            if ($this->isSpecial()) {
                $compiler->repr(true);
            } else {
                $compiler->raw('array_key_exists(')->repr($name)->raw(', $context)');
            }
        } elseif ($this->isSpecial()) {
            $compiler->raw($this->specialVars[$name]);
        } else {
            // remove the non-PHP 5.4 version when PHP 5.3 support is dropped
            // as the non-optimized version is just a workaround for slow ternary operator
            // when the context has a lot of variables
            if (version_compare(phpversion(), '5.4.0RC1', '>=') && ($this->getAttribute('ignore_strict_check') || !$compiler->getEnvironment()->isStrictVariables())) {
                // PHP 5.4 ternary operator performance was optimized
                $compiler
                    ->raw('(isset($context[')
                    ->string($name)
                    ->raw(']) ? $context[')
                    ->string($name)
                    ->raw('] : null)')
                ;
            } else {
                $compiler
                    ->raw('$this->getContext($context, ')
                    ->string($name)
                ;

                if ($this->getAttribute('ignore_strict_check')) {
                    $compiler->raw(', true');
                }

                $compiler
                    ->raw(')')
                ;
            }
        }
    }

    public function isSpecial()
    {
        return isset($this->specialVars[$this->getAttribute('name')]);
    }

    public function isSimple()
    {
        return !$this->isSpecial() && !$this->getAttribute('is_defined_test');
    }
}
