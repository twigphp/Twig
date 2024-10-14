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

namespace Twig\Node\Expression;

use Twig\Compiler;
use Twig\Error\SyntaxError;

class AssignNameExpression extends NameExpression
{
    public function __construct(string $name, int $lineno)
    {
        // All names supported by ExpressionParser::parsePrimaryExpression() should be excluded
        if (\in_array(strtolower($name), ['true', 'false', 'none', 'null'])) {
            throw new SyntaxError(\sprintf('You cannot assign a value to "%s".', $name), $lineno);
        }

        parent::__construct($name, $lineno);
    }

    public function compile(Compiler $compiler): void
    {
        $compiler
            ->raw('$context[')
            ->string($this->getAttribute('name'))
            ->raw(']')
        ;
    }
}
