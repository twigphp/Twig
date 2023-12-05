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
use Twig\Environment;
use Twig\Extension\SandboxExtension;
use Twig\Template;
use Twig\TypeHint\ArrayType;
use Twig\TypeHint\ObjectType;
use Twig\TypeHint\TypeInterface;
use Twig\TypeHint\UnionType;

class GetAttrExpression extends AbstractExpression
{
    public function __construct(AbstractExpression $node, AbstractExpression $attribute, ?AbstractExpression $arguments, string $type, int $lineno)
    {
        $nodes = ['node' => $node, 'attribute' => $attribute];
        if (null !== $arguments) {
            $nodes['arguments'] = $arguments;
        }

        parent::__construct($nodes, ['type' => $type, 'is_defined_test' => false, 'ignore_strict_check' => false, 'optimizable' => true], $lineno);
    }

    public function compile(Compiler $compiler): void
    {
        $env = $compiler->getEnvironment();

        if ($this->getNode('attribute') instanceof ConstantExpression) {
            $type = null;

            if ($this->getNode('node')->hasAttribute('typeHint')) {
                $type = $this->getNode('node')->getAttribute('typeHint');
            }

            if ($type instanceof TypeInterface) {
                $sourceCompiler = $this->createNodeSourceCompiler();
                $accessCompiler = $this->createAccessCompiler($type, $env);

                if (true || $accessCompiler['condition'] === null) {
                    $accessCompiler['accessor']($compiler, $sourceCompiler);
                } else {
                    $compiler->raw('(');
                    $accessCompiler['condition']($compiler, $sourceCompiler);
                    $compiler->raw(' ? ');
                    $accessCompiler['accessor']($compiler, $sourceCompiler);
                    $compiler->raw(' : ');
                    $this->createGuessingAccessCompiler($env->hasExtension(SandboxExtension::class))['accessor']($compiler, $sourceCompiler);
                    $compiler->raw(')');
                }

                return;
            }
        }

        // optimize array calls
        if (
            $this->getAttribute('optimizable')
            && (!$env->isStrictVariables() || $this->getAttribute('ignore_strict_check'))
            && !$this->getAttribute('is_defined_test')
            && Template::ARRAY_CALL === $this->getAttribute('type')
        ) {
            $var = '$'.$compiler->getVarName();
            $compiler
                ->raw('(('.$var.' = ')
                ->subcompile($this->getNode('node'))
                ->raw(') && is_array(')
                ->raw($var)
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

        $this->createGuessingAccessCompiler($env->hasExtension(SandboxExtension::class))['accessor']($compiler, $this->createNodeSourceCompiler());
    }

    /**
     * @return array{
     *     condition: \Closure(Compiler, \Closure(Compiler): void): void|null,
     *     accessor: \Closure(Compiler, \Closure(Compiler): void): void
     * }
     */
    private function createAccessCompiler(TypeInterface $type, Environment $env): array
    {
        if ($type instanceof UnionType) {
            return $this->createUnionAccessCompiler($type, $env);
        }

        if ($type instanceof ArrayType) {
            return $this->createArrayAccessCompiler();
        }

        if ($type instanceof ObjectType && $this->getNode('attribute') instanceof ConstantExpression) {
            $attributeName = $this->getNode('attribute')->getAttribute('value');

            if ($type->getPropertyType($attributeName) !== null) {
                return $this->createObjectPropertyAccessCompiler($type, $attributeName);
            }

            /** Keep similar to @see \Twig\TypeHint\ObjectType::getAttributeType */
            $methodNames = [
                $attributeName,
                'get' . $attributeName,
                'is' . $attributeName,
                'has' . $attributeName,
            ];

            foreach ($methodNames as $methodName) {
                if ($type->getMethodType($methodName) === null) {
                    continue;
                }

                return $this->createObjectMethodAccessCompiler($type, $methodName);
            }
        }

        return $this->createGuessingAccessCompiler($env->hasExtension(SandboxExtension::class));
    }

    /**
     * @return array{
     *     condition: null,
     *     accessor: \Closure(Compiler, \Closure(Compiler): void): void
     * }
     */
    private function createGuessingAccessCompiler(bool $isSandboxed): array
    {
        return [
            'condition' => null,
            'accessor' => function (Compiler $compiler, \Closure $sourceCompiler) use ($isSandboxed): void {
                $compiler->raw('CoreExtension::getAttribute($this->env, $this->source, ');

                if ($this->getAttribute('ignore_strict_check')) {
                    $this->getNode('node')->setAttribute('ignore_strict_check', true);
                }

                $sourceCompiler($compiler);

                $compiler
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
                    ->raw(', ')->repr($this->getAttribute('is_defined_test'))
                    ->raw(', ')->repr($this->getAttribute('ignore_strict_check'))
                    ->raw(', ')->repr($isSandboxed)
                    ->raw(', ')->repr($this->getNode('node')->getTemplateLine())
                    ->raw(')')
                ;
            },
        ];
    }

    /**
     * @return array{
     *     condition: \Closure(Compiler, \Closure(Compiler): void): void,
     *     accessor: \Closure(Compiler, \Closure(Compiler): void): void
     * }
     */
    private function createObjectMethodAccessCompiler(ObjectType $type, string $attributeName): array
    {
        return [
            'condition' => function (Compiler $compiler, \Closure $sourceCompiler) use ($type): void {
                $sourceCompiler($compiler);
                $compiler->raw(' instanceof \\')->raw($type->getType());
            },
            'accessor' => function (Compiler $compiler, \Closure $sourceCompiler) use ($attributeName): void {
                $compiler->raw('(');

                $sourceCompiler($compiler);

                $compiler->raw('?->')->raw($attributeName)->raw('(');

                if ($this->hasNode('arguments') && $this->getNode('arguments') instanceof ArrayExpression && $this->getNode('arguments')->count() > 0) {
                    for ($argIndex = 0; $argIndex < $this->getNode('arguments')->count(); $argIndex += 2) {
                        if ($argIndex > 0) {
                            $compiler->raw(', ');
                        }

                        $compiler->subcompile($this->getNode('arguments')->getNode($argIndex + 1));
                    }
                }

                $compiler->raw('))');
            },
        ];
    }

    /**
     * @return array{
     *     condition: \Closure(Compiler, \Closure(Compiler): void): void,
     *     accessor: \Closure(Compiler, \Closure(Compiler): void): void
     * }
     */
    private function createObjectPropertyAccessCompiler(ObjectType $type, string $attributeName): array
    {
        return [
            'condition' => function (Compiler $compiler, \Closure $sourceCompiler) use ($type): void {
                $sourceCompiler($compiler);
                $compiler->raw(' instanceof \\')->raw($type->getType());
            },
            'accessor' => function (Compiler $compiler, \Closure $sourceCompiler) use ($attributeName): void {
                $sourceCompiler($compiler);
                $compiler
                    ->raw('?->')
                    ->raw($attributeName);
            },
        ];
    }

    /**
     * @return array{
     *     condition: null,
     *     accessor: \Closure(Compiler, \Closure(Compiler): void): void
     * }
     */
    private function createUnionAccessCompiler(UnionType $type, Environment $env): array
    {
        $accessors = [];

        foreach ($type->getTypes() as $innerType) {
            $accessors[] = $this->createAccessCompiler($innerType, $env);
        }

        return [
            'condition' => null,
            'accessor' => function (Compiler $compiler, \Closure $sourceCompiler) use ($accessors) {
                $compiler->raw('match ([');
                $compiler->indent();
                $sourceCompiler($compiler);
                $compiler->raw(", true][1]) {\n");

                foreach ($accessors as $accessor) {
                    if ($accessor['condition'] === null) {
                        $compiler->raw('default');
                    } else {
                        $accessor['condition']($compiler, $sourceCompiler);
                    }

                    $compiler->raw(' => ');
                    $accessor['accessor']($compiler, $sourceCompiler);
                    $compiler->raw(";\n");
                }

                $compiler->outdent();
                $compiler->raw('}');
            }
        ];
    }

    /**
     * @return array{
     *     condition: \Closure(Compiler, \Closure(Compiler): void): void,
     *     accessor: \Closure(Compiler, \Closure(Compiler): void): void
     * }
     */
    private function createArrayAccessCompiler(): array
    {
        return [
            'condition' => function (Compiler $compiler, \Closure $sourceCompiler): void {
                $compiler->raw('(\is_array(');
                $sourceCompiler($compiler);
                $compiler->raw(') || ');
                $sourceCompiler($compiler);
                $compiler->raw(' instanceof \\ArrayAccess)');
            },
            'accessor' => function (Compiler $compiler, \Closure $sourceCompiler): void {
                $compiler->raw('(');
                $sourceCompiler($compiler);
                $compiler
                    ->raw('[')
                    ->subcompile($this->getNode('attribute'))
                    ->raw('] ?? null)');
            },
        ];
    }

    /**
     * @return \Closure(Compiler): void
     */
    private function createAutoInlineSourceCompiler(): \Closure
    {
        $varName = null;
        $sourceCompiler = $this->createNodeSourceCompiler();

        return function (Compiler $compiler) use (&$varName, &$sourceCompiler): void {
            if ($varName === null) {
                $varName = $compiler->getVarName();
                $newSourceCompiler = $this->createVarNameSourceCompiler($varName);

                $compiler->raw('(');
                $newSourceCompiler($compiler);
                $compiler->raw(' = ');
                $sourceCompiler($compiler);
                $compiler->raw(')');

                $sourceCompiler = $newSourceCompiler;
            } else {
                $sourceCompiler($compiler);
            }
        };
    }

    /**
     * @return \Closure(Compiler): void
     */
    private function createNodeSourceCompiler(): \Closure
    {
        return function (Compiler $compiler): void {
            $compiler->subcompile($this->getNode('node'));
        };
    }

    /**
     * @return \Closure(Compiler): void
     */
    private function createVarNameSourceCompiler(string $varName): \Closure
    {
        return function (Compiler $compiler) use ($varName): void {
            $compiler
                ->raw('$')
                ->raw($varName)
            ;
        };
    }
}
