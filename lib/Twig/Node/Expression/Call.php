<?php

/*
 * This file is part of Twig.
 *
 * (c) 2012 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
abstract class Twig_Node_Expression_Call extends Twig_Node_Expression
{
    protected function compileCallable(Twig_Compiler $compiler)
    {
        $callable = $this->getAttribute('callable');

        $closingParenthesis = false;
        if (is_string($callable)) {
            $compiler->raw($callable);
        } elseif (is_array($callable) && $callable[0] instanceof Twig_ExtensionInterface) {
            $compiler->raw(sprintf('$this->env->getExtension(\'%s\')->%s', $callable[0]->getName(), $callable[1]));
        } else {
            $closingParenthesis = true;
            $compiler->raw(sprintf('call_user_func_array($this->env->get%s(\'%s\')->getCallable(), array', ucfirst($this->getAttribute('type')), $this->getAttribute('name')));
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

        if ($this->hasNode('arguments') && null !== $this->getNode('arguments')) {
            $callable = $this->getAttribute('callable');
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
        $callType = $this->getAttribute('type');
        $callName = $this->getAttribute('name');

        $parameters = array();
        $named = false;
        foreach ($arguments as $name => $node) {
            if (!is_int($name)) {
                $named = true;
                $name = $this->normalizeName($name);
            } elseif ($named) {
                throw new Twig_Error_Syntax(sprintf('Positional arguments cannot be used after named arguments for %s "%s".', $callType, $callName));
            }

            $parameters[$name] = $node;
        }

        if (!$named) {
            return $parameters;
        }

        if (!$callable) {
            throw new LogicException(sprintf('Named arguments are not supported for %s "%s".', $callType, $callName));
        }

        // manage named arguments
        if (is_array($callable)) {
            $r = new ReflectionMethod($callable[0], $callable[1]);
        } elseif (is_object($callable) && !$callable instanceof Closure) {
            $r = new ReflectionObject($callable);
            $r = $r->getMethod('__invoke');
        } elseif (is_string($callable) && false !== strpos($callable, '::')) {
            $r = new ReflectionMethod($callable);
        } else {
            $r = new ReflectionFunction($callable);
        }

        $definition = $r->getParameters();
        if ($this->hasNode('node')) {
            array_shift($definition);
        }
        if ($this->hasAttribute('needs_environment') && $this->getAttribute('needs_environment')) {
            array_shift($definition);
        }
        if ($this->hasAttribute('needs_context') && $this->getAttribute('needs_context')) {
            array_shift($definition);
        }
        if ($this->hasAttribute('arguments') && null !== $this->getAttribute('arguments')) {
            foreach ($this->getAttribute('arguments') as $argument) {
                array_shift($definition);
            }
        }

        $arguments = array();
        $names = array();
        $missingArguments = array();
        $optionalArguments = array();
        $pos = 0;
        foreach ($definition as $param) {
            $names[] = $name = $this->normalizeName($param->name);

            if (array_key_exists($name, $parameters)) {
                if (array_key_exists($pos, $parameters)) {
                    throw new Twig_Error_Syntax(sprintf('Argument "%s" is defined twice for %s "%s".', $name, $callType, $callName));
                }

                if (!empty($missingArguments)) {
                    throw new Twig_Error_Syntax(sprintf(
                        'Argument "%s" could not be assigned for %s "%s(%s)" because it is mapped to an internal PHP function which cannot determine default value for optional argument%s "%s".',
                        $name, $callType, $callName, implode(', ', $names), count($missingArguments) > 1 ? 's' : '', implode('", "', $missingArguments))
                    );
                }

                $arguments = array_merge($arguments, $optionalArguments);
                $arguments[] = $parameters[$name];
                unset($parameters[$name]);
                $optionalArguments = array();
            } elseif (array_key_exists($pos, $parameters)) {
                $arguments = array_merge($arguments, $optionalArguments);
                $arguments[] = $parameters[$pos];
                unset($parameters[$pos]);
                $optionalArguments = array();
                ++$pos;
            } elseif ($param->isDefaultValueAvailable()) {
                $optionalArguments[] = new Twig_Node_Expression_Constant($param->getDefaultValue(), -1);
            } elseif ($param->isOptional()) {
                if (empty($parameters)) {
                    break;
                } else {
                    $missingArguments[] = $name;
                }
            } else {
                throw new Twig_Error_Syntax(sprintf('Value for argument "%s" is required for %s "%s".', $name, $callType, $callName));
            }
        }

        if (!empty($parameters)) {
            $unknownParameter = null;
            foreach ($parameters as $parameter) {
                if ($parameter instanceof Twig_Node) {
                    $unknownParameter = $parameter;
                    break;
                }
            }

            throw new Twig_Error_Syntax(sprintf(
                'Unknown argument%s "%s" for %s "%s(%s)".',
                count($parameters) > 1 ? 's' : '', implode('", "', array_keys($parameters)), $callType, $callName, implode(', ', $names)
            ), $unknownParameter ? $unknownParameter->getLine() : -1);
        }

        return $arguments;
    }

    protected function normalizeName($name)
    {
        return strtolower(preg_replace(array('/([A-Z]+)([A-Z][a-z])/', '/([a-z\d])([A-Z])/'), array('\\1_\\2', '\\1_\\2'), $name));
    }
}
