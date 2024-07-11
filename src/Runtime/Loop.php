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

use Twig\Error\RuntimeError;

/**
 * Represents a for loop variable.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @internal
 */
final class Loop implements \Iterator
{
    private \Iterator $seq;
    private bool $iterated;
    private int $index0;
    private int $revindex0;
    private bool $first;
    private bool $last;
    private int $length;

    public function __construct($seq, private $parent)
    {
        $this->seq = is_iterable($seq) ? (is_array($seq) ? new \ArrayIterator($seq) : $seq) : new \ArrayIterator([]);
        $this->rewind();
    }

    public function current(): mixed
    {
        return $this->seq->current();
    }

    public function key(): mixed
    {
        return $this->seq->key();
    }

    public function next(): void
    {
        $this->seq->next();

        $this->iterated = true;
        ++$this->index0;
        $this->first = false;
        if (isset($this->length)) {
            --$this->revindex0;
            $this->last = 0 === $this->revindex0;
        }
    }

    public function rewind(): void
    {
        $this->seq->rewind();

        $this->iterated = false;
        $this->index0 = 0;
        $this->first = true;
        if ($this->seq instanceof \Countable) {
            $length = count($this->seq);
            $this->revindex0 = $length - 1;
            $this->length = $length;
            $this->last = 1 === $length;
        }
    }

    public function valid(): bool
    {
        return $this->seq->valid();
    }

    public function iterated(): bool
    {
        return $this->iterated;
    }

    public function getParent(): mixed
    {
        return $this->parent;
    }

    public function getRevindex0(): int
    {
        if (!$this->seq instanceof \Countable) {
            throw new RuntimeError('The "loop.revindex0" variable is not defined as the loop iterates on a non-countable iterator.');
        }

        return $this->revindex0;
    }

    public function getIndex0(): int
    {
        return $this->index0;
    }

    public function getLength(): int
    {
        if (!$this->seq instanceof \Countable) {
            throw new RuntimeError('The "loop.length" variable is not defined as the loop iterates on a non-countable iterator.');
        }

        return $this->length;
    }

    public function isFirst(): bool
    {
        return $this->first;
    }

    public function isLast(): bool
    {
        if (!$this->seq instanceof \Countable) {
            throw new RuntimeError('The "loop.last" variable is not defined as the loop iterates on a non-countable iterator.');
        }

        return $this->last;
    }
}
