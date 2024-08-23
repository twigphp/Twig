<?php

namespace Twig\Node;

use Twig\Compiler;
use Twig\Node\Expression\ArrayExpression;

/**
 * Represents a types node.
 *
 * @author Jeroen Versteeg <jeroen@alisqi.com>
 * @see https://github.com/twigphp/Twig/issues/4165
 */
class TypesNode extends Node implements NodeCaptureInterface
{
    public function __construct(ArrayExpression $typesNode, int $lineno, ?string $tag = null)
    {
        parent::__construct(['mapping' => $typesNode], [], $lineno, $tag);
    }

    public function compile(Compiler $compiler)
    {
        // Don't compile anything.
    }
}
