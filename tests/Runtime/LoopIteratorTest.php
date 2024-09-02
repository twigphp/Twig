<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Tests\Runtime;

use PHPUnit\Framework\TestCase;
use Twig\Runtime\LoopIterator;

class LoopIteratorTest extends TestCase
{
    /**
     * @dataProvider provideIterablesForNext
     */
    public function testNextWhenValid(iterable $iterable)
    {
        $iterator = new LoopIterator($iterable);
        $iterator->next();

        $this->assertTrue($iterator->valid());
        $this->assertSame(1, $iterator->key());
        $this->assertSame('bar', $iterator->current());
    }

    /**
     * @dataProvider provideIterablesForNext
     */
    public function testNextWhenNotValid(iterable $iterable)
    {
        $iterator = new LoopIterator($iterable);
        $iterator->next();
        $iterator->next();

        $this->assertFalse($iterator->valid());
        $this->assertNull($iterator->key());
        $this->assertNull($iterator->current());
    }

    public function provideIterablesForNext()
    {
        yield [['foo', 'bar']];
        yield [new \ArrayIterator(['foo', 'bar'])];
        yield [new TypedArrayIterator(['foo', 'bar'])];
    }

    /**
     * @dataProvider provideIterablesForRewind
     */
    public function testRewind(iterable $iterable)
    {
        $iterator = new LoopIterator($iterable);
        $iterator->next();

        $this->assertTrue($iterator->valid());
        $this->assertSame(1, $iterator->key());
        $this->assertSame('bar', $iterator->current());

        $iterator->rewind();

        $this->assertTrue($iterator->valid());
        $this->assertSame(0, $iterator->key());
        $this->assertSame('foo', $iterator->current());
    }

    public function provideIterablesForRewind()
    {
        yield [['foo', 'bar']];
        yield [new \ArrayIterator(['foo', 'bar'])];
        yield [new TypedArrayIterator(['foo', 'bar'])];
    }
}

class TypedArrayIterator implements \Iterator
{
    public function __construct(
        private array $values,
    ) {
    }

    public function current(): string
    {
        return current($this->values);
    }

    public function next(): void
    {
        next($this->values);
    }

    public function key(): int
    {
        return key($this->values);
    }

    public function valid(): bool
    {
        return null !== key($this->values);
    }

    public function rewind(): void
    {
        reset($this->values);
    }
}
