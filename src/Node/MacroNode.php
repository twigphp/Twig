<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Node;

use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Error\SyntaxError;
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Expression\Variable\LocalVariable;
use Twig\TwigMacro;

/**
 * Represents a macro node.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
#[YieldReady]
final class MacroNode extends Node
{
    private const RESERVED_NAME_PREFIX = "\u{035C}";

    public function __construct(string $name, BodyNode $body, ArrayExpression $arguments, int $lineno, ?string $variadicName = null)
    {
        $seen = [];
        foreach ($arguments->getKeyValuePairs() as $pair) {
            $argName = $pair['key']->getAttribute('name');
            if (null !== $variadicName && $variadicName === $this->stripReservedPrefix($argName)) {
                throw new SyntaxError(\sprintf('The variadic argument "%s" in macro "%s" cannot have the same name as another argument.', $variadicName, $name), $pair['value']->getTemplateLine(), $pair['value']->getSourceContext());
            }
            if (isset($seen[$argName])) {
                throw new SyntaxError(\sprintf('Argument "%s" is defined twice for macro "%s".', $this->stripReservedPrefix($argName), $name), $pair['value']->getTemplateLine(), $pair['value']->getSourceContext());
            }
            $seen[$argName] = true;
        }

        parent::__construct(['body' => $body, 'arguments' => $arguments], ['name' => $name, 'variadic_name' => $variadicName], $lineno);
    }

    public function compile(Compiler $compiler): void
    {
        $variadicName = $this->getAttribute('variadic_name');
        $variadicVariable = null === $variadicName ? null : (\in_array($variadicName, LocalVariable::RESERVED_NAMES, true) ? self::RESERVED_NAME_PREFIX.$variadicName : $variadicName);

        /** @var ArrayExpression $arguments */
        $arguments = $this->getNode('arguments');

        $compiler
            ->raw('new \\'.TwigMacro::class.'(')
            ->string($this->getAttribute('name'))
            ->raw(', function (')
        ;

        $first = true;
        foreach ($arguments->getKeyValuePairs() as $pair) {
            if (!$first) {
                $compiler->raw(', ');
            }
            $first = false;
            $compiler
                ->subcompile($pair['key'])
                ->raw(' = ')
                ->subcompile($pair['value'])
            ;
        }
        if (null !== $variadicVariable) {
            if (!$first) {
                $compiler->raw(', ');
            }
            $compiler->raw('...$'.$variadicVariable);
        }

        $compiler
            ->raw("): string|Markup {\n")
            ->indent()
            ->addDebugInfo($this)
            ->write("\$macros = \$this->macros;\n")
            ->write("\$context = [\n")
            ->indent()
        ;

        foreach ($arguments->getKeyValuePairs() as $pair) {
            $var = $this->stripReservedPrefix($pair['key']->getAttribute('name'));
            $compiler
                ->write('')
                ->string($var)
                ->raw(' => ')
                ->subcompile($pair['key'])
                ->raw(",\n")
            ;
        }

        $node = new CaptureNode($this->getNode('body'), $this->getNode('body')->lineno);

        if (null !== $variadicVariable) {
            $compiler
                ->write('')
                ->string($variadicName)
                ->raw(' => ')
                ->raw('$'.$variadicVariable.",\n")
            ;
        }

        $compiler
            ->outdent()
            ->write("] + \$this->env->getGlobals();\n\n")
            ->write("\$blocks = [];\n\n")
            ->write('return ')
            ->subcompile($node)
            ->raw("\n")
            ->outdent()
            ->write('}, ')
        ;

        $signature = [];
        foreach ($arguments->getKeyValuePairs() as $pair) {
            $default = $pair['value'];
            $signature[$this->stripReservedPrefix($pair['key']->getAttribute('name'))] = !($default->hasAttribute('is_implicit') && $default->getAttribute('is_implicit'));
        }

        $compiler
            ->repr($signature)
            ->raw(', '.(null !== $variadicName ? 'true' : 'false').')')
        ;
    }

    private function stripReservedPrefix(string $name): string
    {
        return str_starts_with($name, self::RESERVED_NAME_PREFIX) ? substr($name, \strlen(self::RESERVED_NAME_PREFIX)) : $name;
    }
}
