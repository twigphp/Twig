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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Twig\Error\RuntimeError;
use Twig\Runtime\LoopIterator;

class LoopIteratorTest extends TestCase
{
    #[DataProvider('provideIterables')]
    public function testIterate(iterable $iterable): void
    {
        $iterator = new LoopIterator($iterable);

        $this->assertSame([0 => 'foo', 1 => 'bar', 2 => 'baz'], iterator_to_array($iterator));
        $this->assertSame(3, $iterator->getIndex0());
        $this->assertSame([0 => 'foo', 1 => 'bar', 2 => 'baz'], iterator_to_array($iterator));
    }

    #[DataProvider('provideIterables')]
    public function testPreviousAndNext(iterable $iterable): void
    {
        $iterator = new LoopIterator($iterable);

        $seen = [];
        foreach ($iterator as $key => $value) {
            $seen[] = [$iterator->getIndex0(), $key, $value, $iterator->getPrevious(), $iterator->hasNext(), $iterator->getNext()];
        }

        $this->assertSame([
            [0, 0, 'foo', null, true, 'bar'],
            [1, 1, 'bar', 'foo', true, 'baz'],
            [2, 2, 'baz', 'bar', false, null],
        ], $seen);
        $this->assertFalse($iterator->hasNext());
        $this->assertNull($iterator->getNext());
    }

    #[DataProvider('provideIterables')]
    public function testLookingAheadDoesNotSkipItems(iterable $iterable): void
    {
        $iterator = new LoopIterator($iterable);

        $seen = [];
        foreach ($iterator as $key => $value) {
            $iterator->getNext();
            $iterator->getNext();
            $seen[$key] = $value;
        }

        $this->assertSame([0 => 'foo', 1 => 'bar', 2 => 'baz'], $seen);
    }

    public static function provideIterables(): iterable
    {
        yield [['foo', 'bar', 'baz']];
        yield [new \ArrayIterator(['foo', 'bar', 'baz'])];
        yield [new \ArrayObject(['foo', 'bar', 'baz'])];
        yield [new TypedArrayIterator(['foo', 'bar', 'baz'])];
    }

    public function testIterateNonIterable(): void
    {
        $iterator = new LoopIterator(null);

        $this->assertSame([], iterator_to_array($iterator));
        $this->assertSame(0, $iterator->getIndex0());
        $this->assertSame(0, $iterator->getLength());
        $this->assertFalse($iterator->hasNext());
    }

    public function testGetLength(): void
    {
        $this->assertSame(2, (new LoopIterator(['foo', 'bar']))->getLength());
        $this->assertSame(2, (new LoopIterator(new \ArrayIterator(['foo', 'bar'])))->getLength());

        $this->expectException(RuntimeError::class);
        $this->expectExceptionMessage('The "loop.revindex" variable is not defined as the loop iterates on a non-countable iterator.');

        (new LoopIterator(new TypedArrayIterator(['foo', 'bar'])))->getLength('revindex');
    }

    public function testIterateGenerator(): void
    {
        $iterator = new LoopIterator((static function () {
            yield 'a' => 'foo';
            yield 'b' => 'bar';
        })());

        $seen = [];
        foreach ($iterator as $key => $value) {
            $seen[$key] = [$value, $iterator->getNext()];
        }

        $this->assertSame(['a' => ['foo', 'bar'], 'b' => ['bar', null]], $seen);
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
