<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig;

/**
 * Represents a precedence change for an operator.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class OperatorPrecedenceChange
{
    public function __construct(
        private string $package,
        private string $version,
        private int $newPrecedence,
    ) {
    }

    public function getPackage(): string
    {
        return $this->package;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getNewPrecedence(): string
    {
        return $this->newPrecedence;
    }
}
