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
    use SupportDefinedTestTrait;

    public function __construct(AbstractExpression $node, AbstractExpression $attribute, ArrayExpression|ContextVariable|null $arguments, string $type, int $lineno)
    {
        $nodes = ['node' => $node, 'attribute' => $attribute];
        if (null !== $arguments) {
            $nodes['arguments'] = $arguments;
        }

        parent::__construct($nodes, ['type' => $type, 'ignore_strict_check' => false, 'optimizable' => true], $lineno);
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
            $compiler->raw(', arguments: ')->subcompile($this->getNode('arguments'));
        }

        if (Template::ANY_CALL !== $type = $this->getAttribute('type')) {
            $compiler->raw(', type: ')->repr($type);
        }

        if ($this->definedTest) {
            $compiler->raw(', isDefinedTest: true');
        }

        if ($this->getAttribute('ignore_strict_check')) {
            $compiler->raw(', ignoreStrictCheck: true');
        }

        if ($env->hasExtension(SandboxExtension::class)) {
            $compiler->raw(', sandboxed: true');
        }

        $compiler
            ->raw(', lineno: ')->repr($this->getNode('node')->getTemplateLine())
            ->raw(')')
        ;

        if ($arrayAccessSandbox) {
            $compiler->raw(')');
        }
    }

    private function changeIgnoreStrictCheck(self $node): void
    {
        $node->setAttribute('optimizable', false);
        $node->setAttribute('ignore_strict_check', true);

        if ($node->getNode('node') instanceof self) {
            $this->changeIgnoreStrictCheck($node->getNode('node'));
        }
    }
}
