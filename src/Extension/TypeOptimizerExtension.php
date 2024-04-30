<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Extension;

use Twig\NodeVisitor\TypeEvaluateNodeVisitor;

/**
 * Extension to improve PHP code compiling by predicting variable types of expressions.
 *
 * @author Joshua Behrens <code@joshua-behrens.de>
 */
final class TypeOptimizerExtension extends AbstractExtension
{
    public function getTokenParsers(): array
    {
        return [];
    }

    public function getNodeVisitors(): array
    {
        return [new TypeEvaluateNodeVisitor()];
    }
}
