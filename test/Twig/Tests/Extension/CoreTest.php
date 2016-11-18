<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Twig_Tests_Extension_CoreTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getRandomFunctionTestData
     */
    public function testRandomFunction($value, $expectedInArray)
    {
        $env = new Twig_Environment($this->getMockBuilder('Twig_LoaderInterface')->getMock());

        for ($i = 0; $i < 100; ++$i) {
            $this->assertTrue(in_array(twig_random($env, $value), $expectedInArray, true)); // assertContains() would not consider the type
        }
    }

    public function getRandomFunctionTestData()
    {
        return array(
            array(// array
                array('apple', 'orange', 'citrus'),
                array('apple', 'orange', 'citrus'),
            ),
            array(// Traversable
                new ArrayObject(array('apple', 'orange', 'citrus')),
                array('apple', 'orange', 'citrus'),
            ),
            array(// unicode string
                'Ä€é',
                array('Ä', '€', 'é'),
            ),
            array(// numeric but string
                '123',
                array('1', '2', '3'),
            ),
            array(// integer
                5,
                range(0, 5, 1),
            ),
            array(// float
                5.9,
                range(0, 5, 1),
            ),
            array(// negative
                -2,
                array(0, -1, -2),
            ),
        );
    }

    public function testRandomFunctionWithoutParameter()
    {
        $max = mt_getrandmax();

        for ($i = 0; $i < 100; ++$i) {
            $val = twig_random(new Twig_Environment($this->getMockBuilder('Twig_LoaderInterface')->getMock()));
            $this->assertTrue(is_int($val) && $val >= 0 && $val <= $max);
        }
    }

    public function testRandomFunctionReturnsAsIs()
    {
        $this->assertSame('', twig_random(new Twig_Environment($this->getMockBuilder('Twig_LoaderInterface')->getMock()), ''));
        $this->assertSame('', twig_random(new Twig_Environment($this->getMockBuilder('Twig_LoaderInterface')->getMock(), array('charset' => null)), ''));

        $instance = new stdClass();
        $this->assertSame($instance, twig_random(new Twig_Environment($this->getMockBuilder('Twig_LoaderInterface')->getMock()), $instance));
    }

    /**
     * @expectedException Twig_Error_Runtime
     */
    public function testRandomFunctionOfEmptyArrayThrowsException()
    {
        twig_random(new Twig_Environment($this->getMockBuilder('Twig_LoaderInterface')->getMock()), array());
    }

    public function testRandomFunctionOnNonUTF8String()
    {
        if (!function_exists('iconv') && !function_exists('mb_convert_encoding')) {
            $this->markTestSkipped('needs iconv or mbstring');
        }

        $twig = new Twig_Environment($this->getMockBuilder('Twig_LoaderInterface')->getMock());
        $twig->setCharset('ISO-8859-1');

        $text = twig_convert_encoding('Äé', 'ISO-8859-1', 'UTF-8');
        for ($i = 0; $i < 30; ++$i) {
            $rand = twig_random($twig, $text);
            $this->assertTrue(in_array(twig_convert_encoding($rand, 'UTF-8', 'ISO-8859-1'), array('Ä', 'é'), true));
        }
    }

    public function testReverseFilterOnNonUTF8String()
    {
        if (!function_exists('iconv') && !function_exists('mb_convert_encoding')) {
            $this->markTestSkipped('needs iconv or mbstring');
        }

        $twig = new Twig_Environment($this->getMockBuilder('Twig_LoaderInterface')->getMock());
        $twig->setCharset('ISO-8859-1');

        $input = twig_convert_encoding('Äé', 'ISO-8859-1', 'UTF-8');
        $output = twig_convert_encoding(twig_reverse_filter($twig, $input), 'UTF-8', 'ISO-8859-1');

        $this->assertEquals($output, 'éÄ');
    }

    public function testCustomEscaper()
    {
        $twig = new Twig_Environment($this->getMockBuilder('Twig_LoaderInterface')->getMock());
        $twig->getExtension('Twig_Extension_Core')->setEscaper('foo', 'foo_escaper_for_test');

        $this->assertEquals('fooUTF-8', twig_escape_filter($twig, 'foo', 'foo'));
        $this->assertEquals('UTF-8', twig_escape_filter($twig, null, 'foo'));
        $this->assertEquals('42UTF-8', twig_escape_filter($twig, 42, 'foo'));
    }

    /**
     * @expectedException Twig_Error_Runtime
     */
    public function testUnknownCustomEscaper()
    {
        twig_escape_filter(new Twig_Environment($this->getMockBuilder('Twig_LoaderInterface')->getMock()), 'foo', 'bar');
    }

    public function testTwigFirst()
    {
        $twig = new Twig_Environment($this->getMockBuilder('Twig_LoaderInterface')->getMock());
        $this->assertEquals('a', twig_first($twig, 'abc'));
        $this->assertEquals(1, twig_first($twig, array(1, 2, 3)));
        $this->assertSame('', twig_first($twig, null));
        $this->assertSame('', twig_first($twig, ''));
    }

    public function testTwigLast()
    {
        $twig = new Twig_Environment($this->getMockBuilder('Twig_LoaderInterface')->getMock());
        $this->assertEquals('c', twig_last($twig, 'abc'));
        $this->assertEquals(3, twig_last($twig, array(1, 2, 3)));
        $this->assertSame('', twig_last($twig, null));
        $this->assertSame('', twig_last($twig, ''));
    }

    /**
     * @param array $expected keys expected
     * @param mixed $input
     *
     * @dataProvider provideArrayKeyCases
     */
    public function testArrayKeysFilter(array $expected, $input)
    {
        $this->assertSame($expected, twig_get_array_keys_filter($input));
    }

    public function provideArrayKeyCases()
    {
        $array = array('a' => 'a1', 'b' => 'b1', 'c' => 'c1');
        $keys = array_keys($array);

        return array(
            array($keys, $array),
            array($keys, new CoreTestIterator($array, $keys)),
            array($keys, new CoreTestIteratorAggregate($array, $keys)),
            array($keys, new CoreTestIteratorAggregateAggregate($array, $keys)),
            array(array(), null),
            array(array('a'), new SimpleXMLElement('<xml><a></a></xml>')),
        );
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
        $array = array(1, 2, 'a' => 3, 5, 6, 7);
        $keys = array_keys($array);

        return array(
            array(true, 1, $array),
            array(true, '3', $array),
            array(true, 1, new CoreTestIterator($array, $keys)),
            array(true, '3', new CoreTestIterator($array, $keys)),
            array(false, 4, $array),
            array(false, 4, new CoreTestIterator($array, $keys)),
            array(false, 1, 1),
            array(true, 'b', new SimpleXMLElement('<xml><a>b</a></xml>')),
        );
    }
}

function foo_escaper_for_test(Twig_Environment $env, $string, $charset)
{
    return $string.$charset;
}

final class CoreTestIteratorAggregate implements IteratorAggregate
{
    private $iterator;

    public function __construct(array $array, array $keys)
    {
        $this->iterator = new CoreTestIterator($array, $keys);
    }

    public function getIterator()
    {
        return $this->iterator;
    }
}

final class CoreTestIteratorAggregateAggregate implements IteratorAggregate
{
    private $iterator;

    public function __construct(array $array, array $keys)
    {
        $this->iterator = new CoreTestIteratorAggregate($array, $keys);
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

    public function __construct(array $array, array $keys)
    {
        $this->array = $array;
        $this->arrayKeys = $keys;
        $this->position = 0;
    }

    public function rewind()
    {
        $this->position = 0;
    }

    public function current()
    {
        return $this->array[$this->key()];
    }

    public function key()
    {
        return $this->arrayKeys[$this->position];
    }

    public function next()
    {
        ++$this->position;
    }

    public function valid()
    {
        return isset($this->arrayKeys[$this->position]);
    }
}
