<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Node\Expression\Binary;

use Twig\Compiler;
use Twig\Error\SyntaxError;
use Twig\Node\Expression\AbstractExpression;
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Expression\Variable\ContextVariable;
use Twig\Node\Node;

/**
 * @internal
 */
class ObjectDestructuringSetBinary extends AbstractBinary
{
    private array $properties = [];

    /**
     * @param ArrayExpression    $left  The array expression containing object/mapping destructuring properties
     * @param AbstractExpression $right The expression providing values for assignment
     */
    public function __construct(Node $left, Node $right, int $lineno)
    {
        if (!$left instanceof ArrayExpression) {
            throw new \LogicException('Left side must be ArrayExpression for object/mapping destructuring.');
        }
        foreach ($left->getKeyValuePairs() as $pair) {
            if (!$pair['value'] instanceof ContextVariable) {
                throw new SyntaxError(\sprintf('Cannot assign to "%s", only variables can be assigned in object/mapping destructuring.', $pair['value']::class), $lineno);
            }
            $this->properties[] = $pair['value']->getAttribute('name');
        }

        parent::__construct($left, $right, $lineno);
    }

    public function compile(Compiler $compiler): void
    {
        $compiler->addDebugInfo($this);
        $compiler->raw('[');
        foreach ($this->properties as $i => $property) {
            if ($i) {
                $compiler->raw(', ');
            }
            $compiler->raw('$context[')->repr($property)->raw(']');
        }
        $compiler->raw('] = [');
        foreach ($this->properties as $i => $property) {
            if ($i) {
                $compiler->raw(', ');
            }
            $compiler->raw('CoreExtension::getAttribute($this->env, $this->source, ')->subcompile($this->getNode('right'))->raw(', ')->repr($property)->raw(', [], \\Twig\\Template::ANY_CALL, false, false, false, ')->repr($this->getNode('right')->getTemplateLine())->raw(')');
        }
        $compiler->raw(']');
    }

    public function operator(Compiler $compiler): Compiler
    {
        return $compiler->raw('=');
    }
}
