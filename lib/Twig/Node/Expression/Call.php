<?php

/*
 * This file is part of Twig.
 *
 * (c) 2012 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
abstract class Twig_Node_Expression_Call extends Twig_Node_Expression_GeneralCall
{
    protected function compileCallable(Twig_Compiler $compiler)
    {
        $closingParenthesis = false;
        if ($this->hasAttribute('callable') && $callable = $this->getAttribute('callable')) {
            if (is_string($callable) && false === strpos($callable, '::')) {
                $compiler->raw($callable);
            } else {
                list($r, $callable) = $this->reflectCallable($callable);
                if ($r instanceof ReflectionMethod && is_string($callable[0])) {
                    if ($r->isStatic()) {
                        $compiler->raw(sprintf('%s::%s', $callable[0], $callable[1]));
                    } else {
                        $compiler->raw(sprintf('$this->env->getRuntime(\'%s\')->%s', $callable[0], $callable[1]));
                    }
                } elseif ($r instanceof ReflectionMethod && $callable[0] instanceof Twig_ExtensionInterface) {
                    $compiler->raw(sprintf('$this->env->getExtension(\'%s\')->%s', get_class($callable[0]), $callable[1]));
                } else {
                    $type = ucfirst($this->getAttribute('type'));
                    $compiler->raw(sprintf('call_user_func_array($this->env->get%s(\'%s\')->getCallable(), array', $type, $this->getAttribute('name')));
                    $closingParenthesis = true;
                }
            }
        } else {
            $compiler->raw($this->getAttribute('thing')->compile());
        }

        $this->compileArguments($compiler);

        if ($closingParenthesis) {
            $compiler->raw(')');
        }
    }

    protected function compileArguments(Twig_Compiler $compiler)
    {
        $compiler->raw('(');

        $first = true;

        if ($this->hasAttribute('needs_environment') && $this->getAttribute('needs_environment')) {
            $compiler->raw('$this->env');
            $first = false;
        }

        if ($this->hasAttribute('needs_context') && $this->getAttribute('needs_context')) {
            if (!$first) {
                $compiler->raw(', ');
            }
            $compiler->raw('$context');
            $first = false;
        }

        if ($this->hasAttribute('arguments')) {
            foreach ($this->getAttribute('arguments') as $argument) {
                if (!$first) {
                    $compiler->raw(', ');
                }
                $compiler->string($argument);
                $first = false;
            }
        }

        if ($this->hasNode('node')) {
            if (!$first) {
                $compiler->raw(', ');
            }
            $compiler->subcompile($this->getNode('node'));
            $first = false;
        }

        if ($this->hasNode('arguments')) {
            $callable = $this->hasAttribute('callable') ? $this->getAttribute('callable') : null;

            $arguments = $this->getArguments($callable, $this->getNode('arguments'));

            foreach ($arguments as $node) {
                if (!$first) {
                    $compiler->raw(', ');
                }
                $compiler->subcompile($node);
                $first = false;
            }
        }

        $compiler->raw(')');
    }

    protected function getArguments($callable, $arguments)
    {
        $type = $this->getAttribute('type');
        $name = $this->getAttribute('name');
        $isVariadic = $this->hasAttribute('is_variadic') && $this->getAttribute('is_variadic');

        return $this->getArgumentsForCallable($callable, $arguments, $type, $name, $isVariadic);
    }

    protected function getParameters(ReflectionFunctionAbstract $reflection)
    {
        $parameters = parent::getParameters($reflection);

        if ($this->hasNode('node')) {
            array_shift($parameters);
        }
        if ($this->hasAttribute('needs_environment') && $this->getAttribute('needs_environment')) {
            array_shift($parameters);
        }
        if ($this->hasAttribute('needs_context') && $this->getAttribute('needs_context')) {
            array_shift($parameters);
        }
        if ($this->hasAttribute('arguments') && null !== $this->getAttribute('arguments')) {
            foreach ($this->getAttribute('arguments') as $argument) {
                array_shift($parameters);
            }
        }

        return $parameters;
    }
}
