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

use Twig\Compiler;
use Twig\Node\Expression\ConstantExpression;

/**
 * Represents a set node.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class SetNode extends Node implements NodeCaptureInterface
{
    public function __construct($capture, \Twig_NodeInterface $names, \Twig_NodeInterface $values, $lineno, $tag = null)
    {
        parent::__construct(['names' => $names, 'values' => $values], ['capture' => $capture, 'safe' => false], $lineno, $tag);

        if ($this->getAttribute('capture')) {
            $this->setAttribute('safe', true);
        }
    }

    public function compile(Compiler $compiler)
    {
        $compiler->addDebugInfo($this);

        if (\count($this->getNode('names')) > 1) {
            $compiler->write('list(');
            foreach ($this->getNode('names') as $idx => $node) {
                if ($idx) {
                    $compiler->raw(', ');
                }

                $compiler->subcompile($node);
            }
            $compiler->raw(')');
        } else {
            $compiler->subcompile($this->getNode('names'), false);
        }

        $compiler->raw(' = ');

        if (\count($this->getNode('names')) > 1) {
            $compiler->write('[');
            foreach ($this->getNode('values') as $idx => $value) {
                if ($idx) {
                    $compiler->raw(', ');
                }

                $compiler->subcompile($value);
            }
            $compiler->raw(']');
        } else {
            if ($this->getAttribute('safe')) {
                $compiler
                    ->raw("('' === \$tmp = ")
                    ->subcompile($this->getNode('values'))
                    ->raw(") ? '' : new Markup(\$tmp, \$this->env->getCharset())")
                ;
            } else {
                $compiler->subcompile($this->getNode('values'));
            }
        }

        $compiler->raw(";\n");
    }
}

class_alias('Twig\Node\SetNode', 'Twig_Node_Set');
