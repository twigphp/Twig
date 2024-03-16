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

namespace Twig\Node;

use Twig\Attribute\YieldReady;
use Twig\Compiler;

/**
 * Represents a comment node.
 *
 * @author Jeroen Versteeg <jeroen@alisqi.com>
 */
#[YieldReady]
class CommentNode extends Node
{
    public function __construct(string $data, int $lineno)
    {
        parent::__construct([], ['text' => $data], $lineno);
    }

    public function compile(Compiler $compiler): void
    {
        // skip comments in compilation
    }
}
