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
        for ($i = 0; $i < 100; $i++) {
            $this->assertTrue(in_array(twig_random($value), $expectedInArray, true)); // assertContains() would not consider the type
        }
    }

    public function getRandomFunctionTestData()
    {
        return array(
            array( // array
                array('apple', 'orange', 'citrus'),
                array('apple', 'orange', 'citrus'),
            ),
            array( // Traversable
                new ArrayObject(array('apple', 'orange', 'citrus')),
                array('apple', 'orange', 'citrus'),
            ),
            array( // unicode string
                'Ä€é',
                array('Ä', '€', 'é'),
            ),
            array( // numeric but string
                '123',
                array('1', '2', '3'),
            ),
            array( // integer
                5,
                range(0, 5, 1),
            ),
            array( // float
                5.9,
                range(0, 5, 1),
            ),
            array( // negative
                -2,
                array(0, -1, -2),
            ),
        );
    }

    public function testRandomFunctionWithoutParameter()
    {
        $max = mt_getrandmax();

        for ($i = 0; $i < 100; $i++) {
            $val = twig_random();
            $this->assertTrue(is_int($val) && $val >= 0 && $val <= $max);
        }
    }

    public function testRandomFunctionReturnsAsIs()
    {
        $this->assertSame('', twig_random(''));

        $instance = new stdClass();
        $this->assertSame($instance, twig_random($instance));
    }

    /**
     * @expectedException Twig_Error_Runtime
     */
    public function testRandomFunctionOfEmptyArrayThrowsException()
    {
        twig_random(array());
    }
}
