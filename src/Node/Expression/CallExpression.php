<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Node\Expression;

use Twig\Compiler;
use Twig\Error\SyntaxError;
use Twig\Extension\ExtensionInterface;
use Twig\Node\Node;
use Twig\Util\CallableArgumentsExtractor;
use Twig\Util\ReflectionCallable;

abstract class CallExpression extends AbstractExpression
{
    private ?ReflectionCallable $reflector = null;

    public function compile(Compiler $compiler): void
    {
        $this->compileCallable($compiler);
    }

    protected function compileCallable(Compiler $compiler)
    {
        $twigCallable = $this->getAttribute('twig_callable');
        $callable = $twigCallable->getCallable();

        if (\is_string($callable) && !str_contains($callable, '::')) {
            $compiler->raw($callable);
        } else {
            $rc = $this->reflectCallable($callable);
            $r = $rc->getReflector();
            $callable = $rc->getCallable();

            if (\is_string($callable)) {
                $compiler->raw($callable);
            } elseif (\is_array($callable) && \is_string($callable[0])) {
                if (!$r instanceof \ReflectionMethod || $r->isStatic()) {
                    $compiler->raw(\sprintf('%s::%s', $callable[0], $callable[1]));
                } else {
                    $compiler->raw(\sprintf('$this->env->getRuntime(\'%s\')->%s', $callable[0], $callable[1]));
                }
            } elseif (\is_array($callable) && $callable[0] instanceof ExtensionInterface) {
                $class = $callable[0]::class;
                if (!$compiler->getEnvironment()->hasExtension($class)) {
                    // Compile a non-optimized call to trigger a \Twig\Error\RuntimeError, which cannot be a compile-time error
                    $compiler->raw(\sprintf('$this->env->getExtension(\'%s\')', $class));
                } else {
                    $compiler->raw(\sprintf('$this->extensions[\'%s\']', ltrim($class, '\\')));
                }

                $compiler->raw(\sprintf('->%s', $callable[1]));
            } else {
                $compiler->raw(\sprintf('$this->env->get%s(\'%s\')->getCallable()', ucfirst($this->getAttribute('type')), $twigCallable->getDynamicName()));
            }
        }

        $this->compileArguments($compiler);
    }

    protected function compileArguments(Compiler $compiler): void
    {
        $compiler->raw('(');

        $first = true;

        $twigCallable = $this->getAttribute('twig_callable');

        if ($twigCallable->needsCharset()) {
            $compiler->raw('$this->env->getCharset()');
            $first = false;
        }

        if ($twigCallable->needsEnvironment()) {
            if (!$first) {
                $compiler->raw(', ');
            }
            $compiler->raw('$this->env');
            $first = false;
        }

        if ($twigCallable->needsContext()) {
            if (!$first) {
                $compiler->raw(', ');
            }
            $compiler->raw('$context');
            $first = false;
        }

        foreach ($twigCallable->getArguments() as $argument) {
            if (!$first) {
                $compiler->raw(', ');
            }
            $compiler->string($argument);
            $first = false;
        }

        if ($this->hasNode('node')) {
            if (!$first) {
                $compiler->raw(', ');
            }
            $compiler->subcompile($this->getNode('node'));
            $first = false;
        }

        $arguments = (new CallableArgumentsExtractor($this, $twigCallable))->extractArguments($this->getNode('arguments'));
        foreach ($arguments as $node) {
            if (!$first) {
                $compiler->raw(', ');
            }
            $compiler->subcompile($node);
            $first = false;
        }

        $compiler->raw(')');
    }

    private function reflectCallable($callable): ReflectionCallable
    {
        return $this->reflector ??= new ReflectionCallable($callable, $this->getAttribute('type'), $this->getAttribute('name'));
    }
}
