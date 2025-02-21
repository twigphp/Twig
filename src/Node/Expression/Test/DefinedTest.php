<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Node\Expression\Test;

use Twig\Attribute\FirstClassTwigCallableReady;
use Twig\Compiler;
use Twig\Error\SyntaxError;
use Twig\Node\Expression\AbstractExpression;
use Twig\Node\Expression\SupportDefinedTestInterface;
use Twig\Node\Expression\TestExpression;
use Twig\Node\Node;
use Twig\TwigTest;

/**
 * Checks if a variable is defined in the current context.
 *
 *    {# defined works with variable names and variable attributes #}
 *    {% if foo is defined %}
 *        {# ... #}
 *    {% endif %}
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class DefinedTest extends TestExpression
{
    #[FirstClassTwigCallableReady]
    public function __construct(AbstractExpression $node, TwigTest $name, ?Node $arguments, int $lineno)
    {
        if (!$node instanceof SupportDefinedTestInterface) {
            throw new SyntaxError('The "defined" test only works with simple variables.', $lineno);
        }

        $node->enableDefinedTest();

        parent::__construct($node, $name, $arguments, $lineno);
    }

    public function compile(Compiler $compiler): void
    {
        $compiler->subcompile($this->getNode('node'));
    }
}
