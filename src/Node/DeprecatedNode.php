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
use Twig\Node\Expression\AbstractExpression;

/**
 * Represents a deprecated node.
 *
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
#[YieldReady]
class DeprecatedNode extends Node implements CoercesChildrenToStringInterface
{
    public function __construct(AbstractExpression $expr, int $lineno)
    {
        parent::__construct(['expr' => $expr], [], $lineno);
    }

    public function compile(Compiler $compiler): void
    {
        $compiler->addDebugInfo($this);

        $compiler->write('trigger_deprecation(');
        if ($this->hasNode('package')) {
            $compiler->subcompile($this->getNode('package'));
        } else {
            $compiler->raw("''");
        }
        $compiler->raw(', ');
        if ($this->hasNode('version')) {
            $compiler->subcompile($this->getNode('version'));
        } else {
            $compiler->raw("''");
        }
        $compiler
            ->raw(', sprintf(')
            ->string(\sprintf('%%s in "%%s" at line %d.', $this->getTemplateLine()))
            ->raw(', ')
            ->subcompile($this->getNode('expr'))
            ->raw(', ')
            ->string($this->getTemplateName())
            ->raw("));\n")
        ;
    }

    public function getStringCoercedChildNames(): array
    {
        // the message is formatted by `sprintf()`, and `package` / `version` are typed `string` on trigger_deprecation()
        $names = ['expr'];
        if ($this->hasNode('package')) {
            $names[] = 'package';
        }
        if ($this->hasNode('version')) {
            $names[] = 'version';
        }

        return $names;
    }
}
