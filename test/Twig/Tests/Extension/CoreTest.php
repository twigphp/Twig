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
     * @dataProvider getEscapedStrings
     */
    public function testEscapeFilter($string, $escaped, $format, $charset, $message)
    {
        $env = new Twig_Environment();
        $this->assertEquals($escaped, twig_escape_filter($env, $string, $format, $charset), $message);

        $object = new StubToString();
        $object->string = $string;
        $this->assertEquals($escaped, twig_escape_filter($env, $object, $format, $charset), $message);
    }

    public function getEscapedStrings()
    {
        return array(
            array('', '', 'html', 'UTF-8', 'Empty string is unchanged'),
            array('foo', 'foo', 'html', 'UTF-8', 'Standard string is unchanged'),
            array('<tag>', '&lt;tag&gt;', 'html', 'UTF-8', 'HTML entities are encoded'),
            array('éléphant', 'éléphant', 'html', 'UTF-8', 'UTF-8 characters are unchanged'),
            array(utf8_decode('éléphant'), 'lphant', 'html', 'UTF-8', 'invalid UTF-8 characters are ignored'),
            array(utf8_decode('éléphant'), utf8_decode('éléphant'), 'html', 'ISO-8859-1', 'ISO-8859-1 characters are unchanged'),
        );
    }
}

class StubToString
{
    public $string;

    public function __toString()
    {
        return $this->string;
    }
}
