<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Twig_Tests_Loader_FilesystemTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getSecurityTests
     * @expectedException Twig_Error_Loader
     */
    public function testSecurity($template)
    {
        $loader = new Twig_Loader_Filesystem(array(__DIR__.'/../Fixtures'));
        $loader->getCacheKey($template);
    }

    public function getSecurityTests()
    {
        return array(
            array('..\\AutoloaderTest.php'),
            array('..\\\\\\AutoloaderTest.php'),
            array('../AutoloaderTest.php'),
            array('..////AutoloaderTest.php'),
            array('./../AutoloaderTest.php'),
            array('.\\..\\AutoloaderTest.php'),
            array('././././././../AutoloaderTest.php'),
            array('.\\./.\\./.\\./../AutoloaderTest.php'),
            array('foo/../../AutoloaderTest.php'),
            array('foo\\..\\..\\AutoloaderTest.php'),
            array('foo/../bar/../../AutoloaderTest.php'),
            array('foo/bar/../../../AutoloaderTest.php'),
            array('filters/../../AutoloaderTest.php'),
            array('filters//..//..//AutoloaderTest.php'),
            array('filters\\..\\..\\AutoloaderTest.php'),
            array('filters\\\\..\\\\..\\\\AutoloaderTest.php'),
            array('filters\\//../\\/\\..\\AutoloaderTest.php'),
        );
    }
}
