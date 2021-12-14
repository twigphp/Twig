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

namespace Twig\Node;

use Twig\Compiler;
use Twig\Error\LoaderError;
use Twig\Node\Expression\AbstractExpression;
use Twig\Node\Expression\ConstantExpression;

/**
 * Represents an include node.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class IncludeNode extends Node implements NodeOutputInterface
{
    public function __construct(AbstractExpression $expr, ?AbstractExpression $variables, bool $only, bool $ignoreMissing, int $lineno, string $tag = null)
    {
        $nodes = ['expr' => $expr];
        if (null !== $variables) {
            $nodes['variables'] = $variables;
        }

        parent::__construct($nodes, ['only' => (bool) $only, 'ignore_missing' => (bool) $ignoreMissing], $lineno, $tag);
    }

    public function compile(Compiler $compiler)
    {
        $compiler->addDebugInfo($this);

        if ($this->getAttribute('ignore_missing')) {
            $template = $compiler->getVarName();

            $compiler
                ->write(sprintf("$%s = null;\n", $template))
                ->write("try {\n")
                ->indent()
                ->write(sprintf('$%s = ', $template))
            ;

            $this->addGetTemplate($compiler);

            $compiler
                ->raw(";\n")
                ->outdent()
                ->write("} catch (LoaderError \$e) {\n")
                ->indent()
                ->write("// ignore missing template\n")
                ->outdent()
                ->write("}\n")
                ->write(sprintf("if ($%s) {\n", $template))
                ->indent()
                ->write(sprintf('$%s->display(', $template))
            ;
            $this->addTemplateArguments($compiler);
            $compiler
                ->raw(");\n")
                ->outdent()
                ->write("}\n")
            ;
        } else {
            $this->addGetTemplate($compiler);
            $compiler->raw('->display(');
            $this->addTemplateArguments($compiler);
            $compiler->raw(");\n");
        }
    }

    protected function addGetTemplate(Compiler $compiler)
    {
        $compiler->write('$this->loadTemplate(');

        $expr = $this->getNode('expr');
        if ($expr instanceof ConstantExpression && $compiler->getEnvironment()->getLoader()->exists($expr->getAttribute('value'))) {
            $class = $compiler->getEnvironment()->getTemplateClass($expr->getAttribute('value'));
            $compiler
                ->raw('new TemplateClass(')
                ->repr($class)
                ->raw(', ')
                ->repr($expr->getAttribute('value'))
                ->raw(')')
            ;
        } else {
            $compiler->subcompile($expr);
        }

        $compiler
            ->raw(', ')
            ->repr($this->getTemplateName())
            ->raw(', ')
            ->repr($this->getTemplateLine())
            ->raw(')')
        ;
    }

    protected function addTemplateArguments(Compiler $compiler)
    {
        if (!$this->hasNode('variables')) {
            $compiler->raw(false === $this->getAttribute('only') ? '$context' : '[]');
        } elseif (false === $this->getAttribute('only')) {
            $compiler
                ->raw('twig_array_merge($context, ')
                ->subcompile($this->getNode('variables'))
                ->raw(')')
            ;
        } else {
            $compiler->raw('twig_to_array(');
            $compiler->subcompile($this->getNode('variables'));
            $compiler->raw(')');
        }
    }
}

class_alias('Twig\Node\IncludeNode', 'Twig_Node_Include');
