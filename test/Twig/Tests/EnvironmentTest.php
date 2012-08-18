<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Twig_Tests_EnvironmentTest extends PHPUnit_Framework_TestCase
{
    public function testAutoescapeOption()
    {
        $loader = new Twig_Loader_Array(array(
            'html' => '{{ foo }} {{ foo }}',
            'js'   => '{{ bar }} {{ bar }}',
        ));

        $twig = new Twig_Environment($loader, array(
            'debug'      => true,
            'cache'      => false,
            'autoescape' => array($this, 'escapingStrategyCallback'),
        ));

        $this->assertEquals('foo&lt;br/ &gt; foo&lt;br/ &gt;', $twig->render('html', array('foo' => 'foo<br/ >')));
        $this->assertEquals('foo\x3Cbr\x2F\x20\x3E foo\x3Cbr\x2F\x20\x3E', $twig->render('js', array('bar' => 'foo<br/ >')));
    }

    public function escapingStrategyCallback($filename)
    {
        return $filename;
    }
}
