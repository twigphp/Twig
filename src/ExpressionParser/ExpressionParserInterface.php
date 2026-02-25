<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\ExpressionParser;

interface ExpressionParserInterface
{
    public function __toString(): string;

    public function getName(): string;

    public function getPrecedence(): int;

    public function getPrecedenceChange(): ?PrecedenceChange;

    /**
     * @return array<string>
     */
    public function getAliases(): array;

    /**
     * Returns the operator token strings that this expression parser handles.
     *
     * These are the strings that should be recognized as operator tokens by the Lexer,
     * and used to look up the parser in the registry.
     *
     * For most parsers, this returns the name and aliases. Parsers that don't handle
     * operator tokens should return an empty array.
     *
     * @return list<string>
     */
    public function getOperatorTokens(): array;
}
