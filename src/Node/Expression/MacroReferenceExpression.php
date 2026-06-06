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
use Twig\Node\CoercesChildrenToStringInterface;
use Twig\Node\Expression\Variable\TemplateVariable;

/**
 * Represents a macro call node.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class MacroReferenceExpression extends AbstractExpression implements SupportDefinedTestInterface, CoercesChildrenToStringInterface
{
    use SupportDefinedTestDeprecationTrait;
    use SupportDefinedTestTrait;

    /**
     * @param string|AbstractExpression $name A static macro method name (e.g. "macro_foo") or, for a dynamic
     *                                        call, an expression resolving to the macro name (without the
     *                                        "macro_" prefix, which is added at runtime)
     */
    public function __construct(TemplateVariable $template, string|AbstractExpression $name, AbstractExpression $arguments, int $lineno)
    {
        $nodes = ['template' => $template, 'arguments' => $arguments];
        $attributes = ['name' => null];

        if (\is_string($name)) {
            // The name is emitted as raw PHP in compile() via "->{$name}(...)",
            // so it must be a valid PHP method identifier. Reject anything else
            // as a defense-in-depth against accidental PHP code injection from
            // a caller that forgot to validate user-controlled input.
            if (!preg_match('#^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$#D', $name)) {
                throw new \LogicException(\sprintf('Macro name "%s" is not a valid PHP identifier.', $name));
            }
            $attributes['name'] = $name;
        } else {
            $nodes['name'] = $name;
        }

        parent::__construct($nodes, $attributes, $lineno);
    }

    public function __clone()
    {
        // The template node must not be deep-cloned because its name is
        // lazily generated during compilation and must stay in sync with
        // the AssignTemplateVariable that populates the $macros array.
        $template = $this->nodes['template'];
        parent::__clone();
        $this->nodes['template'] = $template;
    }

    public function compile(Compiler $compiler): void
    {
        if ($this->hasNode('name')) {
            $this->compileDynamic($compiler);

            return;
        }

        if ($this->definedTest) {
            $compiler
                ->subcompile($this->getNode('template'))
                ->raw('->hasMacro(')
                ->repr($this->getAttribute('name'))
                ->raw(', $context')
                ->raw(')')
            ;

            return;
        }

        $compiler
            ->subcompile($this->getNode('template'))
            ->raw('->getTemplateForMacro(')
            ->repr($this->getAttribute('name'))
            ->raw(', $context, ')
            ->repr($this->getTemplateLine())
            ->raw(', $this->getSourceContext())')
            ->raw(\sprintf('->%s', $this->getAttribute('name')))
            ->raw('(...')
            ->subcompile($this->getNode('arguments'))
            ->raw(')')
        ;
    }

    public function getStringCoercedChildNames(): array
    {
        // Dynamic macro names are prefixed via PHP string concatenation at runtime.
        return $this->hasNode('name') ? ['name'] : [];
    }

    private function compileDynamic(Compiler $compiler): void
    {
        // The macro method name is resolved at runtime from a context value;
        // prefixing it with "macro_" constrains the dynamic method call to the
        // template's macro methods only, and getTemplateForMacro()/hasMacro()
        // validate that the method actually exists.
        $var = $compiler->getVarName();

        if ($this->definedTest) {
            $compiler
                ->subcompile($this->getNode('template'))
                ->raw('->hasMacro(\'macro_\'.')
                ->subcompile($this->getNode('name'))
                ->raw(', $context)')
            ;

            return;
        }

        $compiler
            ->subcompile($this->getNode('template'))
            ->raw(\sprintf('->getTemplateForMacro($%s = \'macro_\'.', $var))
            ->subcompile($this->getNode('name'))
            ->raw(', $context, ')
            ->repr($this->getTemplateLine())
            ->raw(', $this->getSourceContext())')
            ->raw(\sprintf('->{$%s}', $var))
            ->raw('(...')
            ->subcompile($this->getNode('arguments'))
            ->raw(')')
        ;
    }
}
