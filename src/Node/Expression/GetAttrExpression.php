<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 * (c) Armin Ronacher
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Node\Expression;

use Twig\Compiler;
use Twig\Extension\SandboxExtension;
use Twig\Node\Expression\Variable\ContextVariable;
use Twig\Template;

class GetAttrExpression extends AbstractExpression implements SupportDefinedTestInterface
{
    use SupportDefinedTestDeprecationTrait;
    use SupportDefinedTestTrait;

    /**
     * @param ArrayExpression|NameExpression|null $arguments
     */
    public function __construct(AbstractExpression $node, AbstractExpression $attribute, ?AbstractExpression $arguments, string $type, int $lineno, bool $isOptionalChain = false)
    {
        $nodes = ['node' => $node, 'attribute' => $attribute];
        if (null !== $arguments) {
            $nodes['arguments'] = $arguments;
        }

        if ($arguments && !$arguments instanceof ArrayExpression && !$arguments instanceof ContextVariable) {
            trigger_deprecation('twig/twig', '3.15', \sprintf('Not passing a "%s" instance as the "arguments" argument of the "%s" constructor is deprecated ("%s" given).', ArrayExpression::class, static::class, $arguments::class));
        }

        parent::__construct($nodes, ['type' => $type, 'ignore_strict_check' => false, 'optimizable' => true, 'is_optional_chain' => $isOptionalChain], $lineno);
    }

    public function enableDefinedTest(): void
    {
        $this->definedTest = true;
        $this->changeIgnoreStrictCheck($this);
    }

    public function compile(Compiler $compiler): void
    {
        $env = $compiler->getEnvironment();
        $arrayAccessSandbox = false;

        if ($this->getAttribute('is_optional_chain')) {
            $var = '$'.$compiler->getVarName();

            $isOptionalName = $this->getNode('node') instanceof NameExpression &&
                $this->getNode('node')->getAttribute('optional_chain', false);

            if ($isOptionalName) {
                $compiler
                    ->raw('(array_key_exists(')
                    ->string($this->getNode('node')->getAttribute('name'))
                    ->raw(', $context) ? ');

                $compiler
                    ->raw('(null !== (')
                    ->raw($var)
                    ->raw(' = $context[')
                    ->string($this->getNode('node')->getAttribute('name'))
                    ->raw(']) ? ');
            } else {
                $compiler
                    ->raw('(null !== (')
                    ->raw($var)
                    ->raw(' = ');

                $this->getNode('node')->setAttribute('ignore_strict_check', true);
                $compiler->subcompile($this->getNode('node'));
                $compiler->raw(') ? ');
            }

            if ($this->getAttribute('type') === Template::METHOD_CALL) {
                $compiler->raw('CoreExtension::getAttribute($this->env, $this->source, ');
                $compiler
                    ->raw($var)
                    ->raw(', ')
                    ->subcompile($this->getNode('attribute'));

                if ($this->hasNode('arguments')) {
                    $compiler->raw(', ')->subcompile($this->getNode('arguments'));
                } else {
                    $compiler->raw(', []');
                }

                $compiler->raw(', ')
                    ->repr($this->getAttribute('type'))
                    ->raw(', ')->repr($this->definedTest ?? false)
                    ->raw(', ')->repr(true) // ignore_strict_check = true для optional chaining
                    ->raw(', ')->repr($env->hasExtension(SandboxExtension::class))
                    ->raw(', ')->repr($this->getNode('node')->getTemplateLine())
                    ->raw(')');
            } elseif ($this->getAttribute('type') === Template::ARRAY_CALL) {
                $compiler->raw('(is_array(')
                    ->raw($var)
                    ->raw(') || ')
                    ->raw($var)
                    ->raw(' instanceof ArrayAccess ? (')
                    ->raw($var)
                    ->raw('[')
                    ->subcompile($this->getNode('attribute'))
                    ->raw('] ?? null) : null)');
            } else {
                $compiler->raw('CoreExtension::getAttribute($this->env, $this->source, ');
                $compiler
                    ->raw($var)
                    ->raw(', ')
                    ->subcompile($this->getNode('attribute'));

                if ($this->hasNode('arguments')) {
                    $compiler->raw(', ')->subcompile($this->getNode('arguments'));
                } else {
                    $compiler->raw(', []');
                }

                $compiler->raw(', ')
                    ->repr($this->getAttribute('type'))
                    ->raw(', ')->repr($this->definedTest ?? false)
                    ->raw(', ')->repr(true) // ignore_strict_check = true для optional chaining
                    ->raw(', ')->repr($env->hasExtension(SandboxExtension::class))
                    ->raw(', ')->repr($this->getNode('node')->getTemplateLine())
                    ->raw(')');
            }

            if ($isOptionalName) {
                $compiler->raw(' : null) : null)');
            } else {
                $compiler->raw(' : null)');
            }

            return;
        }

        if (
            $this->getAttribute('optimizable')
            && (!$env->isStrictVariables() || $this->getAttribute('ignore_strict_check'))
            && !$this->definedTest
            && Template::ARRAY_CALL === $this->getAttribute('type')
        ) {
            $var = '$'.$compiler->getVarName();
            $compiler
                ->raw('(('.$var.' = ')
                ->subcompile($this->getNode('node'))
                ->raw(') && is_array(')
                ->raw($var);

            if (!$env->hasExtension(SandboxExtension::class)) {
                $compiler
                    ->raw(') || ')
                    ->raw($var)
                    ->raw(' instanceof ArrayAccess ? (')
                    ->raw($var)
                    ->raw('[')
                    ->subcompile($this->getNode('attribute'))
                    ->raw('] ?? null) : null)')
                ;

                return;
            }

            $arrayAccessSandbox = true;

            $compiler
                ->raw(') || ')
                ->raw($var)
                ->raw(' instanceof ArrayAccess && in_array(')
                ->raw($var.'::class')
                ->raw(', CoreExtension::ARRAY_LIKE_CLASSES, true) ? (')
                ->raw($var)
                ->raw('[')
                ->subcompile($this->getNode('attribute'))
                ->raw('] ?? null) : ')
            ;
        }

        $compiler->raw('CoreExtension::getAttribute($this->env, $this->source, ');

        if ($this->getAttribute('ignore_strict_check')) {
            $this->getNode('node')->setAttribute('ignore_strict_check', true);
        }

        $compiler
            ->subcompile($this->getNode('node'))
            ->raw(', ')
            ->subcompile($this->getNode('attribute'))
        ;

        if ($this->hasNode('arguments')) {
            $compiler->raw(', ')->subcompile($this->getNode('arguments'));
        } else {
            $compiler->raw(', []');
        }

        $compiler->raw(', ')
            ->repr($this->getAttribute('type'))
            ->raw(', ')->repr($this->definedTest ?? false)
            ->raw(', ')->repr($this->getAttribute('ignore_strict_check'))
            ->raw(', ')->repr($env->hasExtension(SandboxExtension::class))
            ->raw(', ')->repr($this->getNode('node')->getTemplateLine())
            ->raw(')')
        ;

        if ($arrayAccessSandbox) {
            $compiler->raw(')');
        }
    }
    private function changeIgnoreStrictCheck(GetAttrExpression $node): void
    {
        $node->setAttribute('optimizable', false);
        $node->setAttribute('ignore_strict_check', true);

        if ($node->getNode('node') instanceof GetAttrExpression) {
            $this->changeIgnoreStrictCheck($node->getNode('node'));
        }
    }
}
