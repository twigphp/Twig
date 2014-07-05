<?php

/*
 * This file is part of Twig.
 *
 * (c) 2012 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Represents a general call node.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Martin Hasoň <martin.hason@gmail.com>
 */
abstract class Twig_Node_Expression_GeneralCall extends Twig_Node_Expression
{
    private $reflector;

    protected function getArgumentsForCallable($callable, $arguments, $callType, $callName, $isVariadic, $assoc = false)
    {
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

        if (!$named && !$isVariadic && !$assoc) {
            return $parameters;
        }

        if (!$callable) {
            if ($named) {
                $message = sprintf('Named arguments are not supported for %s "%s".', $callType, $callName);
            } else {
                $message = sprintf('Arbitrary positional arguments are not supported for %s "%s".', $callType, $callName);
            }

            throw new LogicException($message);
        }

        $callableParameters = $this->getCallableParameters($callable, $isVariadic);
        $arguments = array();
        $names = array();
        $missingArguments = array();
        $optionalArguments = array();
        $pos = 0;
        foreach ($callableParameters as $callableParameter) {
            $names[] = $name = $this->normalizeName($callableParameter->name);

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
                $arguments[$name] = $parameters[$name];
                unset($parameters[$name]);
                $optionalArguments = array();
            } elseif (array_key_exists($pos, $parameters)) {
                $arguments = array_merge($arguments, $optionalArguments);
                $arguments[$name] = $parameters[$pos];
                unset($parameters[$pos]);
                $optionalArguments = array();
                ++$pos;
            } elseif ($callableParameter->isDefaultValueAvailable()) {
                $optionalArguments[$name] = new Twig_Node_Expression_Constant($callableParameter->getDefaultValue(), -1);
            } elseif ($callableParameter->isOptional()) {
                if (empty($parameters)) {
                    break;
                } else {
                    $missingArguments[] = $name;
                }
            } else {
                throw new Twig_Error_Syntax(sprintf('Value for argument "%s" is required for %s "%s".', $name, $callType, $callName));
            }
        }

        if ($isVariadic) {
            $arbitraryArguments = new Twig_Node_Expression_Array(array(), -1);
            foreach ($parameters as $key => $value) {
                if (is_int($key)) {
                    $arbitraryArguments->addElement($value);
                } else {
                    $arbitraryArguments->addElement($value, new Twig_Node_Expression_Constant($key, -1));
                }
                unset($parameters[$key]);
            }

            if ($arbitraryArguments->count()) {
                $arguments = array_merge($arguments, $optionalArguments);
                $arguments[] = $arbitraryArguments;
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
            ), $unknownParameter ? $unknownParameter->getTemplateLine() : -1);
        }

        return $assoc ? $arguments : array_values($arguments);
    }

    protected function getParameters(ReflectionFunctionAbstract $reflection)
    {
        return $reflection->getParameters();
    }

    protected function normalizeName($name)
    {
        return strtolower(preg_replace(array('/([A-Z]+)([A-Z][a-z])/', '/([a-z\d])([A-Z])/'), array('\\1_\\2', '\\1_\\2'), $name));
    }

    protected function reflectCallable($callable)
    {
        if (null !== $this->reflector) {
            return $this->reflector;
        }

        if (is_array($callable)) {
            if (!method_exists($callable[0], $callable[1])) {
                // __call()
                return array(null, array());
            }
            $r = new ReflectionMethod($callable[0], $callable[1]);
        } elseif (is_object($callable) && !$callable instanceof Closure) {
            $r = new ReflectionObject($callable);
            $r = $r->getMethod('__invoke');
            $callable = array($callable, '__invoke');
        } elseif (is_string($callable) && false !== $pos = strpos($callable, '::')) {
            $class = substr($callable, 0, $pos);
            $method = substr($callable, $pos + 2);
            if (!method_exists($class, $method)) {
                // __staticCall()
                return array(null, array());
            }
            $r = new ReflectionMethod($callable);
            $callable = array($class, $method);
        } else {
            $r = new ReflectionFunction($callable);
        }

        return $this->reflector = array($r, $callable);
    }

    private function getCallableParameters($callable, $isVariadic)
    {
        list($r, $_) = $this->reflectCallable($callable);
        if (null === $r) {
            return array();
        }

        $parameters = $this->getParameters($r);

        if ($isVariadic) {
            $argument = end($parameters);
            if ($argument && $argument->isArray() && $argument->isDefaultValueAvailable() && array() === $argument->getDefaultValue()) {
                array_pop($parameters);
            } else {
                $callableName = $r->name;
                if ($r instanceof ReflectionMethod) {
                    $callableName = $r->getDeclaringClass()->name.'::'.$callableName;
                }

                throw new LogicException(sprintf('The last parameter of "%s" for %s "%s" must be an array with default value, eg. "array $arg = array()".', $callableName, $this->getAttribute('type'), $this->getAttribute('name')));
            }
        }

        return $parameters;
    }
}
