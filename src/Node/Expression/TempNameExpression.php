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

use Twig\Compiler;
use Twig\Error\SyntaxError;

class TempNameExpression extends AbstractExpression
{
    public const RESERVED_NAMES = ['varargs', 'context', 'macros', 'blocks', 'this'];

    public function __construct(string|int $name, int $lineno)
    {
        // All names supported by ExpressionParser::parsePrimaryExpression() should be excluded
        if (\in_array(strtolower($name), ['true', 'false', 'none', 'null'])) {
            throw new SyntaxError(\sprintf('You cannot assign a value to "%s".', $name), $lineno);
        }

        if (is_int($name) || ctype_digit($name)) {
            $name = (int) $name;
        } elseif (in_array($name, self::RESERVED_NAMES)) {
            $name = '_'.$name.'_';
        }

        parent::__construct([], ['name' => $name], $lineno);
    }

    public function compile(Compiler $compiler): void
    {
        $compiler->raw('$'.$this->getAttribute('name'));
    }
}
