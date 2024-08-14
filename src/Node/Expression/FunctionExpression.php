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

use Twig\Attribute\FirstClassTwigCallableReady;
use Twig\Compiler;
use Twig\Node\Node;
use Twig\TwigFunction;

class FunctionExpression extends CallExpression
{
    #[FirstClassTwigCallableReady]
    public function __construct(TwigFunction $function, Node $arguments, int $lineno)
    {
        parent::__construct(['arguments' => $arguments], ['name' => $function->getName(), 'type' => 'function', 'twig_callable' => $function, 'is_defined_test' => false], $lineno);
    }

    public function compile(Compiler $compiler): void
    {
        if ('constant' === $this->getAttribute('name') && $this->getAttribute('is_defined_test')) {
            $this->getNode('arguments')->setNode('checkDefined', new ConstantExpression(true, $this->getTemplateLine()));
        }

        parent::compile($compiler);
    }
}
