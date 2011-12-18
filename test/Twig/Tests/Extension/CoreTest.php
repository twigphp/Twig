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
    public function testRandomFunction()
    {
        $core = new Twig_Extension_Core();

        $items = array('apple', 'orange', 'citrus');
        $values = array(
            $items,
            new ArrayObject($items),
        );
        foreach ($values as $value) {
            for ($i = 0; $i < 100; $i++) {
                $this->assertTrue(in_array(twig_random($value), $items));
            }
        }
    }
}
