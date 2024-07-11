<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Runtime;

/**
 * Represents a for loop context variable.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @internal
 */
final class LoopContext
{
    public function __construct(private Loop $loop)
    {
    }

    public function getParent(): mixed
    {
        return $this->loop->getParent();
    }

    public function getRevindex0(): int
    {
        return $this->loop->getRevindex0();
    }

    public function getRevindex(): int
    {
        return $this->loop->getRevindex0() + 1;
    }

    public function getIndex0(): int
    {
        return $this->loop->getIndex0();
    }

    public function getIndex(): int
    {
        return $this->getIndex0() + 1;
    }

    public function getLength(): int
    {
        return $this->loop->getLength();
    }

    public function isFirst(): bool
    {
        return $this->loop->isFirst();
    }

    public function isLast(): bool
    {
        return $this->loop->isLast();
    }
}
