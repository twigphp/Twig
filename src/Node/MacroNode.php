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
use Twig\Markup;
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Expression\Variable\LocalVariable;

/**
 * Represents a macro node.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
#[YieldReady]
class MacroNode extends Node
{
    public const VARARGS_NAME = 'varargs';

    /**
     * @param BodyNode        $body
     * @param ArrayExpression $arguments
     */
    public function __construct(string $name, Node $body, Node $arguments, int $lineno)
    {
        if (!$body instanceof BodyNode) {
            trigger_deprecation('twig/twig', '3.12', \sprintf('Not passing a "%s" instance as the "body" argument of the "%s" constructor is deprecated ("%s" given).', BodyNode::class, static::class, $body::class));
        }

        if (!$arguments instanceof ArrayExpression) {
            trigger_deprecation('twig/twig', '3.15', \sprintf('Not passing a "%s" instance as the "arguments" argument of the "%s" constructor is deprecated ("%s" given).', ArrayExpression::class, static::class, $arguments::class));

            $args = new ArrayExpression([], $arguments->getTemplateLine());
            foreach ($arguments as $name => $default) {
                $args->addElement($default, new LocalVariable($name, $default->getTemplateLine()));
            }
            $arguments = $args;
        }

        foreach ($arguments->getKeyValuePairs() as $pair) {
            if ('_'.self::VARARGS_NAME.'_' === $pair['key']->getAttribute('name')) {
                throw new SyntaxError(\sprintf('The argument "%s" in macro "%s" cannot be defined because the variable "%s" is reserved for arbitrary arguments.', self::VARARGS_NAME, $name, self::VARARGS_NAME), $pair['value']->getTemplateLine(), $pair['value']->getSourceContext());
            }
        }

        parent::__construct(['body' => $body, 'arguments' => $arguments], ['name' => $name], $lineno);
    }

    public function compile(Compiler $compiler): void
    {
        $compiler
            ->addDebugInfo($this)
            ->write(\sprintf('public function macro_%s(', $this->getAttribute('name')))
        ;

        foreach ($this->getNode('arguments')->getKeyValuePairs() as $pair) {
            $name = $pair['key'];
            $default = $pair['value'];
            $compiler
                ->subcompile($name)
                ->raw(' = ')
                ->subcompile($default)
                ->raw(', ')
            ;
        }

        $compiler
            ->raw('...$varargs')
            ->raw("): string|Markup\n")
            ->write("{\n")
            ->indent()
            ->write("\$macros = \$this->macros;\n")
            ->write("\$context = [\n")
            ->indent()
        ;

        foreach ($this->getNode('arguments')->getKeyValuePairs() as $pair) {
            $name = $pair['key'];
            $compiler
                ->write('')
                ->string(trim($name->getAttribute('name'), '_'))
                ->raw(' => ')
                ->subcompile($name)
                ->raw(",\n")
            ;
        }

        $node = new CaptureNode($this->getNode('body'), $this->getNode('body')->lineno);

        $compiler
            ->write('')
            ->string(self::VARARGS_NAME)
            ->raw(' => ')
            ->raw("\$varargs,\n")
            ->outdent()
            ->write("] + \$this->env->getGlobals();\n\n")
            ->write("\$blocks = [];\n\n")
            ->write('return ')
            ->subcompile($node)
            ->raw("\n")
            ->outdent()
            ->write("}\n\n")
        ;
    }
}
