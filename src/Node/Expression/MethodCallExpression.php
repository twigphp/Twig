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
use Twig\Node\Expression\Variable\ContextVariable;

class MethodCallExpression extends AbstractExpression implements SupportDefinedTestInterface
{
    use SupportDefinedTestDeprecationTrait;
    use SupportDefinedTestTrait;

    public function __construct(AbstractExpression $node, string $method, ArrayExpression $arguments, int $lineno)
    {
        trigger_deprecation('twig/twig', '3.15', 'The "%s" class is deprecated, use "%s" instead.', __CLASS__, MacroReferenceExpression::class);

        parent::__construct(['node' => $node, 'arguments' => $arguments], ['method' => $method, 'safe' => false], $lineno);

        if ($node instanceof ContextVariable) {
            $node->setAttribute('always_defined', true);
        }
    }

    public function compile(Compiler $compiler): void
    {
        if ($this->definedTest) {
            $compiler
                ->raw('method_exists($macros[')
                ->repr($this->getNode('node')->getAttribute('name'))
                ->raw('], ')
                ->repr($this->getAttribute('method'))
                ->raw(')')
            ;

            return;
        }

        $compiler
            ->raw('CoreExtension::callMacro($macros[')
            ->repr($this->getNode('node')->getAttribute('name'))
            ->raw('], ')
            ->repr($this->getAttribute('method'))
            ->raw(', ')
            ->subcompile($this->getNode('arguments'))
            ->raw(', ')
            ->repr($this->getTemplateLine())
            ->raw(', $context, $this->getSourceContext())');
    }
}
