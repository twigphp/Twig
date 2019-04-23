<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Twig\Environment;

class Twig_Tests_Extension_CoreTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider getRandomFunctionTestData
     */
    public function testRandomFunction(array $expectedInArray, $value1, $value2 = null)
    {
        $env = new Twig_Environment($this->getMockBuilder('Twig_LoaderInterface')->getMock());
        for ($i = 0; $i < 100; ++$i) {
            $this->assertTrue(\in_array(twig_random($env, $value1, $value2), $expectedInArray, true)); // assertContains() would not consider the type
        }
    }

    public function getRandomFunctionTestData()
    {
        return [
            'array' => [
                ['apple', 'orange', 'citrus'],
                ['apple', 'orange', 'citrus'],
            ],
            'Traversable' => [
                ['apple', 'orange', 'citrus'],
                new ArrayObject(['apple', 'orange', 'citrus']),
            ],
            'unicode string' => [
                ['Ä', '€', 'é'],
                'Ä€é',
            ],
            'numeric but string' => [
                ['1', '2', '3'],
                '123',
            ],
            'integer' => [
                range(0, 5, 1),
                5,
            ],
            'float' => [
                range(0, 5, 1),
                5.9,
            ],
            'negative' => [
                [0, -1, -2],
                -2,
            ],
            'min max int' => [
                range(50, 100),
                50,
                100,
            ],
            'min max float' => [
                range(-10, 10),
                -9.5,
                9.5,
            ],
            'min null' => [
                range(0, 100),
                null,
                100,
            ],
        ];
    }

    public function testRandomFunctionWithoutParameter()
    {
        $max = mt_getrandmax();

        for ($i = 0; $i < 100; ++$i) {
            $val = twig_random(new Environment($this->getMockBuilder('\Twig\Loader\LoaderInterface')->getMock()));
            $this->assertTrue(\is_int($val) && $val >= 0 && $val <= $max);
        }
    }

    public function testRandomFunctionReturnsAsIs()
    {
        $this->assertSame('', twig_random(new Environment($this->getMockBuilder('\Twig\Loader\LoaderInterface')->getMock()), ''));
        $this->assertSame('', twig_random(new Environment($this->getMockBuilder('\Twig\Loader\LoaderInterface')->getMock(), ['charset' => null]), ''));

        $instance = new \stdClass();
        $this->assertSame($instance, twig_random(new Environment($this->getMockBuilder('\Twig\Loader\LoaderInterface')->getMock()), $instance));
    }

    /**
     * @expectedException \Twig\Error\RuntimeError
     */
    public function testRandomFunctionOfEmptyArrayThrowsException()
    {
        twig_random(new Environment($this->getMockBuilder('\Twig\Loader\LoaderInterface')->getMock()), []);
    }

    public function testRandomFunctionOnNonUTF8String()
    {
        if (!\function_exists('iconv') && !\function_exists('mb_convert_encoding')) {
            $this->markTestSkipped('needs iconv or mbstring');
        }

        $twig = new Environment($this->getMockBuilder('\Twig\Loader\LoaderInterface')->getMock());
        $twig->setCharset('ISO-8859-1');

        $text = twig_convert_encoding('Äé', 'ISO-8859-1', 'UTF-8');
        for ($i = 0; $i < 30; ++$i) {
            $rand = twig_random($twig, $text);
            $this->assertTrue(\in_array(twig_convert_encoding($rand, 'UTF-8', 'ISO-8859-1'), ['Ä', 'é'], true));
        }
    }

    public function testReverseFilterOnNonUTF8String()
    {
        if (!\function_exists('iconv') && !\function_exists('mb_convert_encoding')) {
            $this->markTestSkipped('needs iconv or mbstring');
        }

        $twig = new Environment($this->getMockBuilder('\Twig\Loader\LoaderInterface')->getMock());
        $twig->setCharset('ISO-8859-1');

        $input = twig_convert_encoding('Äé', 'ISO-8859-1', 'UTF-8');
        $output = twig_convert_encoding(twig_reverse_filter($twig, $input), 'UTF-8', 'ISO-8859-1');

        $this->assertEquals($output, 'éÄ');
    }

    /**
     * @dataProvider provideCustomEscaperCases
     */
    public function testCustomEscaper($expected, $string, $strategy)
    {
        $twig = new Environment($this->getMockBuilder('\Twig\Loader\LoaderInterface')->getMock());
        $twig->getExtension('\Twig\Extension\CoreExtension')->setEscaper('foo', 'foo_escaper_for_test');

        $this->assertSame($expected, twig_escape_filter($twig, $string, $strategy));
    }

    public function provideCustomEscaperCases()
    {
        return [
            ['fooUTF-8', 'foo', 'foo'],
            ['UTF-8', null, 'foo'],
            ['42UTF-8', 42, 'foo'],
        ];
    }

    /**
     * @expectedException \Twig\Error\RuntimeError
     */
    public function testUnknownCustomEscaper()
    {
        twig_escape_filter(new Environment($this->getMockBuilder('\Twig\Loader\LoaderInterface')->getMock()), 'foo', 'bar');
    }

    /**
     * @dataProvider provideTwigFirstCases
     */
    public function testTwigFirst($expected, $input)
    {
        $twig = new Environment($this->getMockBuilder('\Twig\Loader\LoaderInterface')->getMock());
        $this->assertSame($expected, twig_first($twig, $input));
    }

    public function provideTwigFirstCases()
    {
        $i = [1 => 'a', 2 => 'b', 3 => 'c'];

        return [
            ['a', 'abc'],
            [1, [1, 2, 3]],
            ['', null],
            ['', ''],
            ['a', new CoreTestIterator($i, array_keys($i), true, 3)],
        ];
    }

    /**
     * @dataProvider provideTwigLastCases
     */
    public function testTwigLast($expected, $input)
    {
        $twig = new Environment($this->getMockBuilder('\Twig\Loader\LoaderInterface')->getMock());
        $this->assertSame($expected, twig_last($twig, $input));
    }

    public function provideTwigLastCases()
    {
        $i = [1 => 'a', 2 => 'b', 3 => 'c'];

        return [
            ['c', 'abc'],
            [3, [1, 2, 3]],
            ['', null],
            ['', ''],
            ['c', new CoreTestIterator($i, array_keys($i), true)],
        ];
    }

    /**
     * @dataProvider provideArrayKeyCases
     */
    public function testArrayKeysFilter(array $expected, $input)
    {
        $this->assertSame($expected, twig_get_array_keys_filter($input));
    }

    public function provideArrayKeyCases()
    {
        $array = ['a' => 'a1', 'b' => 'b1', 'c' => 'c1'];
        $keys = array_keys($array);

        return [
            [$keys, $array],
            [$keys, new CoreTestIterator($array, $keys)],
            [$keys, new CoreTestIteratorAggregate($array, $keys)],
            [$keys, new CoreTestIteratorAggregateAggregate($array, $keys)],
            [[], null],
            [['a'], new \SimpleXMLElement('<xml><a></a></xml>')],
        ];
    }

    /**
     * @dataProvider provideInFilterCases
     */
    public function testInFilter($expected, $value, $compare)
    {
        $this->assertSame($expected, twig_in_filter($value, $compare));
    }

    public function provideInFilterCases()
    {
        $array = [1, 2, 'a' => 3, 5, 6, 7];
        $keys = array_keys($array);

        return [
            [true, 1, $array],
            [true, '3', $array],
            [true, '3', 'abc3def'],
            [true, 1, new CoreTestIterator($array, $keys, true, 1)],
            [true, '3', new CoreTestIterator($array, $keys, true, 3)],
            [true, '3', new CoreTestIteratorAggregateAggregate($array, $keys, true, 3)],
            [false, 4, $array],
            [false, 4, new CoreTestIterator($array, $keys, true)],
            [false, 4, new CoreTestIteratorAggregateAggregate($array, $keys, true)],
            [false, 1, 1],
            [true, 'b', new \SimpleXMLElement('<xml><a>b</a></xml>')],
        ];
    }

    /**
     * @dataProvider provideSliceFilterCases
     */
    public function testSliceFilter($expected, $input, $start, $length = null, $preserveKeys = false)
    {
        $twig = new Environment($this->getMockBuilder('\Twig\Loader\LoaderInterface')->getMock());
        $this->assertSame($expected, twig_slice($twig, $input, $start, $length, $preserveKeys));
    }

    public function provideSliceFilterCases()
    {
        $i = ['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4];
        $keys = array_keys($i);

        return [
            [['a' => 1], $i, 0, 1, true],
            [['a' => 1], $i, 0, 1, false],
            [['b' => 2, 'c' => 3], $i, 1, 2],
            [[1], [1, 2, 3, 4], 0, 1],
            [[2, 3], [1, 2, 3, 4], 1, 2],
            [[2, 3], new CoreTestIterator($i, $keys, true), 1, 2],
            [['c' => 3, 'd' => 4], new CoreTestIteratorAggregate($i, $keys, true), 2, null, true],
            [$i, new CoreTestIterator($i, $keys, true), 0, \count($keys) + 10, true],
            [[], new CoreTestIterator($i, $keys, true), \count($keys) + 10],
            ['de', 'abcdef', 3, 2],
            [[], new \SimpleXMLElement('<items><item>1</item><item>2</item></items>'), 3],
            [[], new \ArrayIterator([1, 2]), 3],
        ];
    }

    /**
     * @dataProvider provideCompareCases
     */
    public function testCompare($expected, $a, $b)
    {
        $this->assertSame($expected, twig_compare($a, $b));
        $this->assertSame($expected, twig_compare($b, $a));
    }

    public function provideCompareCases()
    {
        return [
            [true, 'a', 'a'],

            // from https://wiki.php.net/rfc/string_to_number_comparison
            [true, 0, '0'],
            [true, 0, '0.0'],

            [false, 0, 'foo'],
            [false, 0, ''],
            [true, 42, '   42'],
            [false, 42, '42foo'],

            [true, '0', '0'],
            [true, '0', '0.0'],
            [false, '0', 'foo'],
            [false, '0', ''],
            [true, '42', '   42'],
            [false, '42', '42foo'],

            [true, 42, '000042'],
            [true, 42, '42.0'],
            [true, 42.0, '+42.0E0'],
            [true, 0, '0e214987142012'],

            [true, '42', '000042'],
            [true, '42', '42.0'],
            [true, '42.0', '+42.0E0'],
            [true, '0', '0e214987142012'],

            [true, 42, '   42'],
            [true, 42, '42   '],
            [false, 42, '42abc'],
            [false, 42, 'abc42'],
            [false, 0, 'abc42'],
        ];
    }
}

function foo_escaper_for_test(Environment $env, $string, $charset)
{
    return $string.$charset;
}

final class CoreTestIteratorAggregate implements \IteratorAggregate
{
    private $iterator;

    public function __construct(array $array, array $keys, $allowAccess = false, $maxPosition = false)
    {
        $this->iterator = new CoreTestIterator($array, $keys, $allowAccess, $maxPosition);
    }

    public function getIterator()
    {
        return $this->iterator;
    }
}

final class CoreTestIteratorAggregateAggregate implements \IteratorAggregate
{
    private $iterator;

    public function __construct(array $array, array $keys, $allowValueAccess = false, $maxPosition = false)
    {
        $this->iterator = new CoreTestIteratorAggregate($array, $keys, $allowValueAccess, $maxPosition);
    }

    public function getIterator()
    {
        return $this->iterator;
    }
}

final class CoreTestIterator implements Iterator
{
    private $position;
    private $array;
    private $arrayKeys;
    private $allowValueAccess;
    private $maxPosition;

    public function __construct(array $values, array $keys, $allowValueAccess = false, $maxPosition = false)
    {
        $this->array = $values;
        $this->arrayKeys = $keys;
        $this->position = 0;
        $this->allowValueAccess = $allowValueAccess;
        $this->maxPosition = false === $maxPosition ? \count($values) + 1 : $maxPosition;
    }

    public function rewind()
    {
        $this->position = 0;
    }

    public function current()
    {
        if ($this->allowValueAccess) {
            return $this->array[$this->key()];
        }

        throw new \LogicException('Code should only use the keys, not the values provided by iterator.');
    }

    public function key()
    {
        return $this->arrayKeys[$this->position];
    }

    public function next()
    {
        ++$this->position;
        if ($this->position === $this->maxPosition) {
            throw new \LogicException(sprintf('Code should not iterate beyond %d.', $this->maxPosition));
        }
    }

    public function valid()
    {
        return isset($this->arrayKeys[$this->position]);
    }
}
