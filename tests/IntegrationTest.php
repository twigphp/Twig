<?php

namespace Twig\Tests;

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Twig\Extension\AbstractExtension;
use Twig\Extension\DebugExtension;
use Twig\Extension\SandboxExtension;
use Twig\Extension\StringLoaderExtension;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Node;
use Twig\Node\PrintNode;
use Twig\Sandbox\SecurityPolicy;
use Twig\Test\IntegrationTestCase;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

// This function is defined to check that escaping strategies
// like html works even if a function with the same name is defined.
function html()
{
    return 'foo';
}

class IntegrationTest extends IntegrationTestCase
{
    public function getExtensions()
    {
        $policy = new SecurityPolicy([], [], [], [], ['dump']);

        return [
            new DebugExtension(),
            new SandboxExtension($policy, false),
            new StringLoaderExtension(),
            new TwigTestExtension(),
        ];
    }

    public function getFixturesDir()
    {
        return __DIR__.'/Fixtures/';
    }
}

function test_foo($value = 'foo')
{
    return $value;
}

class TwigTestFoo implements \Iterator
{
    public const BAR_NAME = 'bar';

    public $position = 0;
    public $array = [1, 2];

    public function bar($param1 = null, $param2 = null)
    {
        return 'bar'.($param1 ? '_'.$param1 : '').($param2 ? '-'.$param2 : '');
    }

    public function getFoo()
    {
        return 'foo';
    }

    public function getSelf()
    {
        return $this;
    }

    public function is()
    {
        return 'is';
    }

    public function in()
    {
        return 'in';
    }

    public function not()
    {
        return 'not';
    }

    public function strToLower($value)
    {
        return strtolower($value);
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    /**
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function current()
    {
        return $this->array[$this->position];
    }

    /**
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function key()
    {
        return 'a';
    }

    public function next(): void
    {
        ++$this->position;
    }

    public function valid(): bool
    {
        return isset($this->array[$this->position]);
    }
}

class TwigTestTokenParser_§ extends AbstractTokenParser
{
    public function parse(Token $token): Node
    {
        $this->parser->getStream()->expect(Token::BLOCK_END_TYPE);

        return new PrintNode(new ConstantExpression('§', -1), -1);
    }

    public function getTag(): string
    {
        return '§';
    }
}

class TwigTestExtension extends AbstractExtension
{
    public function getTokenParsers(): array
    {
        return [
            new TwigTestTokenParser_§(),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('§', [$this, '§Filter']),
            new TwigFilter('escape_and_nl2br', [$this, 'escape_and_nl2br'], ['needs_environment' => true, 'is_safe' => ['html']]),
            new TwigFilter('nl2br', [$this, 'nl2br'], ['pre_escape' => 'html', 'is_safe' => ['html']]),
            new TwigFilter('escape_something', [$this, 'escape_something'], ['is_safe' => ['something']]),
            new TwigFilter('preserves_safety', [$this, 'preserves_safety'], ['preserves_safety' => ['html']]),
            new TwigFilter('static_call_string', 'Twig\Tests\TwigTestExtension::staticCall'),
            new TwigFilter('static_call_array', ['Twig\Tests\TwigTestExtension', 'staticCall']),
            new TwigFilter('magic_call', [$this, 'magicCall']),
            new TwigFilter('magic_call_closure', \Closure::fromCallable([$this, 'magicCall'])),
            new TwigFilter('magic_call_string', 'Twig\Tests\TwigTestExtension::magicStaticCall'),
            new TwigFilter('magic_call_array', ['Twig\Tests\TwigTestExtension', 'magicStaticCall']),
            new TwigFilter('*_path', [$this, 'dynamic_path']),
            new TwigFilter('*_foo_*_bar', [$this, 'dynamic_foo']),
            new TwigFilter('not', [$this, 'notFilter']),
            new TwigFilter('anon_foo', function ($name) { return '*'.$name.'*'; }),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('§', [$this, '§Function']),
            new TwigFunction('safe_br', [$this, 'br'], ['is_safe' => ['html']]),
            new TwigFunction('unsafe_br', [$this, 'br']),
            new TwigFunction('static_call_string', 'Twig\Tests\TwigTestExtension::staticCall'),
            new TwigFunction('static_call_array', ['Twig\Tests\TwigTestExtension', 'staticCall']),
            new TwigFunction('*_path', [$this, 'dynamic_path']),
            new TwigFunction('*_foo_*_bar', [$this, 'dynamic_foo']),
            new TwigFunction('anon_foo', function ($name) { return '*'.$name.'*'; }),
        ];
    }

    public function getTests(): array
    {
        return [
            new TwigTest('multi word', [$this, 'is_multi_word']),
            new TwigTest('test_*', [$this, 'dynamic_test']),
        ];
    }

    public function notFilter($value)
    {
        return 'not '.$value;
    }

    public function §Filter($value)
    {
        return "§{$value}§";
    }

    public function §Function($value)
    {
        return "§{$value}§";
    }

    /**
     * nl2br which also escapes, for testing escaper filters.
     */
    public function escape_and_nl2br($env, $value, $sep = '<br />')
    {
        return $this->nl2br(twig_escape_filter($env, $value, 'html'), $sep);
    }

    /**
     * nl2br only, for testing filters with pre_escape.
     */
    public function nl2br($value, $sep = '<br />')
    {
        // not secure if $value contains html tags (not only entities)
        // don't use
        return str_replace("\n", "$sep\n", $value ?? '');
    }

    public function dynamic_path($element, $item)
    {
        return $element.'/'.$item;
    }

    public function dynamic_foo($foo, $bar, $item)
    {
        return $foo.'/'.$bar.'/'.$item;
    }

    public function dynamic_test($element, $item)
    {
        return $element === $item;
    }

    public function escape_something($value)
    {
        return strtoupper($value);
    }

    public function preserves_safety($value)
    {
        return strtoupper($value);
    }

    public static function staticCall($value)
    {
        return "*$value*";
    }

    public function br()
    {
        return '<br />';
    }

    public function is_multi_word($value)
    {
        return false !== strpos($value, ' ');
    }

    public function __call($method, $arguments)
    {
        if ('magicCall' !== $method) {
            throw new \BadMethodCallException('Unexpected call to __call');
        }

        return 'magic_'.$arguments[0];
    }

    public static function __callStatic($method, $arguments)
    {
        if ('magicStaticCall' !== $method) {
            throw new \BadMethodCallException('Unexpected call to __callStatic');
        }

        return 'static_magic_'.$arguments[0];
    }
}

/**
 * This class is used in tests for the "length" filter and "empty" test. It asserts that __call is not
 * used to convert such objects to strings.
 */
class MagicCallStub
{
    public function __call($name, $args)
    {
        throw new \Exception('__call shall not be called');
    }
}

class ToStringStub
{
    /**
     * @var string
     */
    private $string;

    public function __construct($string)
    {
        $this->string = $string;
    }

    public function __toString()
    {
        return $this->string;
    }
}

/**
 * This class is used in tests for the length filter and empty test to show
 * that when \Countable is implemented, it is preferred over the __toString()
 * method.
 */
class CountableStub implements \Countable
{
    private $count;

    public function __construct($count)
    {
        $this->count = $count;
    }

    public function count(): int
    {
        return $this->count;
    }

    public function __toString()
    {
        throw new \Exception('__toString shall not be called on \Countables');
    }
}

/**
 * This class is used in tests for the length filter.
 */
class IteratorAggregateStub implements \IteratorAggregate
{
    private $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->data);
    }
}

class SimpleIteratorForTesting implements \Iterator
{
    private $data = [1, 2, 3, 4, 5, 6, 7];
    private $key = 0;

    /**
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function current()
    {
        return $this->key;
    }

    public function next(): void
    {
        ++$this->key;
    }

    /**
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function key()
    {
        return $this->key;
    }

    public function valid(): bool
    {
        return isset($this->data[$this->key]);
    }

    public function rewind(): void
    {
        $this->key = 0;
    }

    public function __toString()
    {
        // for testing, make sure string length returned is not the same as the `iterator_count`
        return str_repeat('X', iterator_count($this) + 10);
    }
}
