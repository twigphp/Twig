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

use Twig\NodeVisitor\YieldingNodeVisitor;

class YieldingExtension extends AbstractExtension
{
    private $yielding;

    public function __construct(bool $yielding)
    {
        $this->yielding = $yielding;
    }

    public function getNodeVisitors(): array
    {
        return [new YieldingNodeVisitor($this->yielding)];
    }
}
