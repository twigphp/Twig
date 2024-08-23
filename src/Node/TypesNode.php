<?php

namespace Twig\Node;

use Twig\Compiler;
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\NameExpression;

/**
 * Represents a types node.
 *
 * @author Jeroen Versteeg <jeroen@alisqi.com>
 * @see    https://github.com/twigphp/Twig/issues/4165
 */
class TypesNode extends Node implements NodeCaptureInterface
{
    public function __construct(ArrayExpression $typesNode, int $lineno, ?string $tag = null)
    {
        $this->validateMapping($typesNode);

        parent::__construct(['mapping' => $typesNode], [], $lineno, $tag);
    }

    protected function validateMapping(ArrayExpression $typesNode): void
    {
        foreach ($typesNode->getKeyValuePairs() as $i => $pair) {
            $keyExpression = $pair['key'];
            $valueExpression = $pair['value'];

            if (!$keyExpression instanceof NameExpression) {
                throw new \InvalidArgumentException("Key at index $i is not a NameExpression");
            }
            $name = $keyExpression->getAttribute('name');

            if (!$valueExpression instanceof ConstantExpression) {
                throw new \InvalidArgumentException("Value for key \"$name\" is not a ConstantExpression");
            }
            $value = $valueExpression->getAttribute('value');

            if (!is_string($value)) {
                throw new \InvalidArgumentException("Value for key \"$name\" is not a string");
            }
        }
    }

    public function compile(Compiler $compiler)
    {
        // Don't compile anything.
    }
}
