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

class FunctionExpression extends CallExpression implements SupportDefinedTestInterface
{
    use SupportDefinedTestTrait;

    #[FirstClassTwigCallableReady]
    public function __construct(TwigFunction $function, Node $arguments, int $lineno)
    {
        parent::__construct(['arguments' => $arguments], ['name' => $function->getName(), 'type' => 'function', 'twig_callable' => $function], $lineno);
    }

    public function enableDefinedTest(): void
    {
        if ('constant' === $this->getAttribute('name')) {
            $this->definedTest = true;
        }
    }

    public function compile(Compiler $compiler): void
    {
        if ('constant' === $this->getAttribute('name') && $this->definedTest) {
            $this->getNode('arguments')->setNode('checkDefined', new ConstantExpression(true, $this->getTemplateLine()));
        }

        parent::compile($compiler);
    }
}
