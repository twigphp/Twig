<?php
/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Twig_Tests_EscaperTest extends PHPUnit_Framework_TestCase
{
    public function testEscape()
    {
        $escaper = new Twig_OutputEscaping_Escaper();
        $string = '<span>hello</span>';
        $escaped = '&lt;span&gt;hello&lt;/span&gt;';
        $this->assertEquals($escaped, $escaper->escape($string, 'html', 'UTF-8'));
    }
}
