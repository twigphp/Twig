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
use Twig\Node\Expression\Variable\TemplateVariable;

/**
 * Represents a macro call node.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class MacroReferenceExpression extends AbstractExpression implements SupportDefinedTestInterface
{
    use SupportDefinedTestDeprecationTrait;
    use SupportDefinedTestTrait;

    public function __construct(TemplateVariable $template, string $name, AbstractExpression $arguments, int $lineno)
    {
        // The name is emitted as raw PHP in compile() via "->{$name}(...)",
        // so it must be a valid PHP method identifier. Reject anything else
        // as a defense-in-depth against accidental PHP code injection from
        // a caller that forgot to validate user-controlled input.
        if (!preg_match('#^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$#D', $name)) {
            throw new \LogicException(\sprintf('Macro name "%s" is not a valid PHP identifier.', $name));
        }

        parent::__construct(['template' => $template, 'arguments' => $arguments], ['name' => $name], $lineno);
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
}
