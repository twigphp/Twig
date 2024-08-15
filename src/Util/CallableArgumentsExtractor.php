<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Util;

use Twig\Error\SyntaxError;
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\VariadicExpression;
use Twig\Node\Node;
use Twig\TwigCallableInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

/**
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @internal
 */
final class CallableArgumentsExtractor
{
    private string $type;
    private string $name;

    public function __construct(
        private Node $node,
        private TwigCallableInterface $twigCallable,
    ) {
        $this->type = match (true) {
            $twigCallable instanceof TwigFunction => 'function',
            $twigCallable instanceof TwigFilter => 'filter',
            $twigCallable instanceof TwigTest => 'test',
            default => throw new \LogicException('Unknown callable type.'),
        };
        $this->name = $twigCallable->getName();
    }

    /**
     * @return array<Node>
     */
    public function extractArguments(Node $arguments): array
    {
        $parameters = [];
        $named = false;
        foreach ($arguments as $name => $node) {
            if (!\is_int($name)) {
                $named = true;
                $name = $this->normalizeName($name);
            } elseif ($named) {
                throw new SyntaxError(\sprintf('Positional arguments cannot be used after named arguments for %s "%s".', $this->type, $this->name), $this->node->getTemplateLine(), $this->node->getSourceContext());
            }

            $parameters[$name] = $node;
        }

        if (!$named && !$this->twigCallable->isVariadic()) {
            return $parameters;
        }

        if (!$callable = $this->twigCallable->getCallable()) {
            if ($named) {
                $message = \sprintf('Named arguments are not supported for %s "%s".', $this->type, $this->name);
            } else {
                $message = \sprintf('Arbitrary positional arguments are not supported for %s "%s".', $this->type, $this->name);
            }

            throw new \LogicException($message);
        }

        [$callableParameters, $isPhpVariadic] = $this->getCallableParameters();
        $arguments = [];
        $names = [];
        $missingArguments = [];
        $optionalArguments = [];
        $pos = 0;
        foreach ($callableParameters as $callableParameter) {
            $name = $this->normalizeName($callableParameter->name);
            if (\PHP_VERSION_ID >= 80000 && 'range' === $callable) {
                if ('start' === $name) {
                    $name = 'low';
                } elseif ('end' === $name) {
                    $name = 'high';
                }
            }

            $names[] = $name;

            if (\array_key_exists($name, $parameters)) {
                if (\array_key_exists($pos, $parameters)) {
                    throw new SyntaxError(\sprintf('Argument "%s" is defined twice for %s "%s".', $name, $this->type, $this->name), $this->node->getTemplateLine(), $this->node->getSourceContext());
                }

                if (\count($missingArguments)) {
                    throw new SyntaxError(\sprintf(
                        'Argument "%s" could not be assigned for %s "%s(%s)" because it is mapped to an internal PHP function which cannot determine default value for optional argument%s "%s".',
                        $name, $this->type, $this->name, implode(', ', $names), \count($missingArguments) > 1 ? 's' : '', implode('", "', $missingArguments)
                    ), $this->node->getTemplateLine(), $this->node->getSourceContext());
                }

                $arguments = array_merge($arguments, $optionalArguments);
                $arguments[] = $parameters[$name];
                unset($parameters[$name]);
                $optionalArguments = [];
            } elseif (\array_key_exists($pos, $parameters)) {
                $arguments = array_merge($arguments, $optionalArguments);
                $arguments[] = $parameters[$pos];
                unset($parameters[$pos]);
                $optionalArguments = [];
                ++$pos;
            } elseif ($callableParameter->isDefaultValueAvailable()) {
                $optionalArguments[] = new ConstantExpression($callableParameter->getDefaultValue(), -1);
            } elseif ($callableParameter->isOptional()) {
                if (empty($parameters)) {
                    break;
                } else {
                    $missingArguments[] = $name;
                }
            } else {
                throw new SyntaxError(\sprintf('Value for argument "%s" is required for %s "%s".', $name, $this->type, $this->name), $this->node->getTemplateLine(), $this->node->getSourceContext());
            }
        }

        if ($this->twigCallable->isVariadic()) {
            $arbitraryArguments = $isPhpVariadic ? new VariadicExpression([], -1) : new ArrayExpression([], -1);
            foreach ($parameters as $key => $value) {
                if (\is_int($key)) {
                    $arbitraryArguments->addElement($value);
                } else {
                    $arbitraryArguments->addElement($value, new ConstantExpression($key, -1));
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
                if ($parameter instanceof Node) {
                    $unknownParameter = $parameter;
                    break;
                }
            }

            throw new SyntaxError(
                \sprintf(
                    'Unknown argument%s "%s" for %s "%s(%s)".',
                    \count($parameters) > 1 ? 's' : '', implode('", "', array_keys($parameters)), $this->type, $this->name, implode(', ', $names)
                ),
                $unknownParameter ? $unknownParameter->getTemplateLine() : $this->node->getTemplateLine(),
                $unknownParameter ? $unknownParameter->getSourceContext() : $this->node->getSourceContext()
            );
        }

        return $arguments;
    }

    private function normalizeName(string $name): string
    {
        return strtolower(preg_replace(['/([A-Z]+)([A-Z][a-z])/', '/([a-z\d])([A-Z])/'], ['\\1_\\2', '\\1_\\2'], $name));
    }

    private function getCallableParameters(): array
    {
        $rc = new ReflectionCallable($this->twigCallable->getCallable(), $this->type, $this->name);
        $r = $rc->getReflector();
        $callableName = $rc->getName();

        $parameters = $r->getParameters();
        if ($this->node->hasNode('node')) {
            array_shift($parameters);
        }
        if ($this->twigCallable->needsCharset()) {
            array_shift($parameters);
        }
        if ($this->twigCallable->needsEnvironment()) {
            array_shift($parameters);
        }
        if ($this->twigCallable->needsContext()) {
            array_shift($parameters);
        }
        foreach ($this->twigCallable->getArguments() as $argument) {
            array_shift($parameters);
        }

        $isPhpVariadic = false;
        if ($this->twigCallable->isVariadic()) {
            $argument = end($parameters);
            $isArray = $argument && $argument->hasType() && $argument->getType() instanceof \ReflectionNamedType && 'array' === $argument->getType()->getName();
            if ($isArray && $argument->isDefaultValueAvailable() && [] === $argument->getDefaultValue()) {
                array_pop($parameters);
            } elseif ($argument && $argument->isVariadic()) {
                array_pop($parameters);
                $isPhpVariadic = true;
            } else {
                throw new \LogicException(\sprintf('The last parameter of "%s" for %s "%s" must be an array with default value, eg. "array $arg = []".', $callableName, $this->type, $this->name));
            }
        }

        return [$parameters, $isPhpVariadic];
    }
}
