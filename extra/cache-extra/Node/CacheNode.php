<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Extra\Cache\Node;

use Twig\Compiler;
use Twig\Node\CaptureNode;
use Twig\Node\Expression\AbstractExpression;
use Twig\Node\Node;

class CacheNode extends AbstractExpression
{
    public function __construct(AbstractExpression $key, ?AbstractExpression $ttl, ?AbstractExpression $tags, Node $body, int $lineno, string $tag)
    {
        $body = new CaptureNode($body, $lineno);
        $body->setAttribute('raw', true);

        $nodes = ['key' => $key, 'body' => $body];
        if (null !== $ttl) {
            $nodes['ttl'] = $ttl;
        }
        if (null !== $tags) {
            $nodes['tags'] = $tags;
        }

        parent::__construct($nodes, [], $lineno, $tag);
    }

    public function compile(Compiler $compiler): void
    {
        $compiler
            ->addDebugInfo($this)
            ->raw('$this->env->getRuntime(\'Twig\Extra\Cache\CacheRuntime\')->getCache()->get(')
            ->subcompile($this->getNode('key'))
            ->raw(", function (\Symfony\Contracts\Cache\ItemInterface \$item) use (\$context, \$macros, \$blocks) {\n")
            ->indent()
        ;

        if ($this->hasNode('tags')) {
            $compiler
                ->write('$item->tag(')
                ->subcompile($this->getNode('tags'))
                ->raw(");\n")
            ;
        }

        if ($this->hasNode('ttl')) {
            $compiler
                ->write('$item->expiresAfter(')
                ->subcompile($this->getNode('ttl'))
                ->raw(");\n")
            ;
        }

        $compiler
            ->write('return ')
            ->subcompile($this->getNode('body'))
            ->raw(";\n")
            ->outdent()
            ->write("})\n")
        ;
    }
}
