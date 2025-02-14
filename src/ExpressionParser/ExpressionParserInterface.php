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
}
