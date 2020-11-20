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

use Twig\NodeVisitor\OptimizerNodeVisitor;

/**
 * Class OptimizerExtension
 * @package Twig\Extension
 */
final class OptimizerExtension extends AbstractExtension
{
    /**
     * @var int
     */
    private $optimizers;

    /**
     * OptimizerExtension constructor.
     * @param int $optimizers
     */
    public function __construct(int $optimizers = -1)
    {
        $this->optimizers = $optimizers;
    }

    /**
     * @return OptimizerNodeVisitor[]
     */
    public function getNodeVisitors(): array
    {
        return [new OptimizerNodeVisitor($this->optimizers)];
    }
}
