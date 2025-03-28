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

        // Если используется optional chaining
        if ($this->getAttribute('is_optional_chain')) {
            $var = '$'.$compiler->getVarName();

            // Проверяем, является ли node NameExpression с флагом optional_chain
            $isOptionalName = $this->getNode('node') instanceof NameExpression &&
                $this->getNode('node')->getAttribute('optional_chain', false);

            if ($isOptionalName) {
                // Безопасный доступ к переменным контекста без выброса исключения
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
                // Обычная проверка null для нормальных выражений
                $compiler
                    ->raw('(null !== (')
                    ->raw($var)
                    ->raw(' = ');

                // Обращение к полю через ->subcompile может вызвать исключение
                $this->getNode('node')->setAttribute('ignore_strict_check', true);
                $compiler->subcompile($this->getNode('node'));
                $compiler->raw(') ? ');
            }

            // Генерируем код для доступа к атрибуту в зависимости от типа
            if ($this->getAttribute('type') === Template::METHOD_CALL) {
                // Вызов метода
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
                // Доступ к массиву
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
                // Доступ к свойству
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

        // Оригинальный код для обычного доступа к атрибутам
        // optimize array calls
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
    private function compileGetAttr(Compiler $compiler, string $varName): void
    {
        $env = $compiler->getEnvironment();

        if (\PHP_VERSION_ID >= 80000) {
            $compiler->raw($varName);
            if ($this->getAttribute('type') === Template::METHOD_CALL) {
                $compiler->raw('?->');
                if ($this->getNode('attribute') instanceof ConstantExpression) {
                    $compiler->raw($this->getNode('attribute')->getAttribute('value'));
                } else {
                    $compiler->raw('{');
                    $compiler->subcompile($this->getNode('attribute'));
                    $compiler->raw('}');
                }

                $compiler->raw('(');

                if ($this->hasNode('arguments')) {
                    $first = true;
                    foreach ($this->getNode('arguments') as $argNode) {
                        if (!$first) {
                            $compiler->raw(', ');
                        }
                        $compiler->subcompile($argNode);
                        $first = false;
                    }
                }

                $compiler->raw(')');
            } else {
                $compiler->raw('?->');

                if ($this->getNode('attribute') instanceof ConstantExpression) {
                    $compiler->raw($this->getNode('attribute')->getAttribute('value'));
                } else {
                    $compiler->raw('{');
                    $compiler->subcompile($this->getNode('attribute'));
                    $compiler->raw('}');
                }
            }
        } else {
            $compiler->raw('CoreExtension::getAttribute($this->env, $this->source, ');
            $compiler
                ->raw($varName)
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
                ->raw(', ')->repr($this->getAttribute('ignore_strict_check'))
                ->raw(', ')->repr($env->hasExtension(SandboxExtension::class))
                ->raw(', ')->repr($this->getNode('node')->getTemplateLine())
                ->raw(')');
        }
    }
}
