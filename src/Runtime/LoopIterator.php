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
 *
 * @template TKey
 * @template TValue
 *
 * @implements \IteratorAggregate<TKey, TValue>
 */
final class LoopIterator implements \IteratorAggregate
{
    /** @var array<TKey, TValue> */
    private array $array = [];
    /** @var \Iterator<TKey, TValue>|null */
    private ?\Iterator $iterator = null;
    private int $index0 = 0;
    private int $length;
    private bool $hasPrevious = false;
    private mixed $previous = null;
    /** @var list<TKey>|null */
    private ?array $keys = null;
    /** @var array{valid: bool, value: mixed}|null */
    private ?array $next = null;

    /**
     * @param iterable<TKey, TValue> $seq
     */
    public function __construct($seq)
    {
        if (\is_array($seq)) {
            $this->array = $seq;
        } elseif ($seq instanceof \IteratorAggregate) {
            do {
                $seq = $seq->getIterator();
            } while ($seq instanceof \IteratorAggregate);
            $this->iterator = $seq;
        } elseif (is_iterable($seq)) {
            /** @var \Iterator<TKey, TValue> $seq */
            $this->iterator = $seq;
        }
    }

    /**
     * @return \Generator<TKey, TValue>
     */
    public function getIterator(): \Generator
    {
        $this->index0 = 0;
        $this->hasPrevious = false;
        $this->previous = null;
        $this->next = null;

        if (null === $iterator = $this->iterator) {
            // arrays are iterated natively as their items can be looked ahead by position
            foreach ($this->array as $key => $value) {
                yield $key => $value;

                $this->hasPrevious = true;
                $this->previous = $value;
                ++$this->index0;
            }

            return;
        }

        for ($iterator->rewind(); $iterator->valid(); ++$this->index0) {
            $key = $iterator->key();
            $value = $iterator->current();

            yield $key => $value;

            $this->hasPrevious = true;
            $this->previous = $value;
            $this->moveForward($iterator);
        }
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

        if (null === $this->iterator) {
            return $this->length = \count($this->array);
        }

        if (!$this->iterator instanceof \Countable) {
            throw new RuntimeError(\sprintf('The "loop.%s" variable is not defined as the loop iterates on a non-countable iterator.', $var));
        }

        return $this->length = \count($this->iterator);
    }

    public function getPrevious(): mixed
    {
        return $this->hasPrevious ? $this->previous : null;
    }

    public function hasNext(): bool
    {
        if (null === $this->iterator) {
            return $this->index0 + 1 < \count($this->array);
        }

        return $this->lookAhead()['valid'];
    }

    public function getNext(): mixed
    {
        return $this->lookAhead()['value'];
    }

    /**
     * @param \Iterator<TKey, TValue> $iterator
     */
    private function moveForward(\Iterator $iterator): void
    {
        // the iterator already moved forward when looking ahead
        if (null === $this->next) {
            $iterator->next();
        }

        $this->next = null;
    }

    /**
     * @return array{valid: bool, value: mixed}
     */
    private function lookAhead(): array
    {
        if (null === $this->iterator) {
            $this->keys ??= array_keys($this->array);

            return isset($this->keys[$i = $this->index0 + 1]) ? ['valid' => true, 'value' => $this->array[$this->keys[$i]]] : ['valid' => false, 'value' => null];
        }

        if (null === $this->next) {
            $this->iterator->next();
            $this->next = $this->iterator->valid() ? ['valid' => true, 'value' => $this->iterator->current()] : ['valid' => false, 'value' => null];
        }

        return $this->next;
    }
}
