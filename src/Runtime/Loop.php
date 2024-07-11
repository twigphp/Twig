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
    private int $index0;
    private int $length;

    public function __construct($seq)
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
        ++$this->index0;
    }

    public function rewind(): void
    {
        $this->seq->rewind();
        $this->index0 = 0;
    }

    public function valid(): bool
    {
        return $this->seq->valid();
    }

    public function iterated(): bool
    {
        return 0 !== $this->index0;
    }

    public function getIndex0(): int
    {
        return $this->index0;
    }

    public function getLength($var = 'length'): int
    {
        if (isset($this->length)) {
            return $this->length;
        }

        if (!$this->seq instanceof \Countable) {
            throw new RuntimeError(sprintf('The "loop.%s" variable is not defined as the loop iterates on a non-countable iterator.', $var));
        }

        return $this->length = count($this->seq);
    }
}
