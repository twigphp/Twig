<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Twig_Tests_NativeExtensionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @requires PHP 5.3
     */
    public function testGetProperties()
    {
        if (PHP_VERSION_ID >= 70000) {
            $this->markTestSkipped('Extension is not available on PHP 7+');
        }

        $twig = new Twig_Environment(new Twig_Loader_Array(['index' => '{{ d1.date }}{{ d2.date }}']), [
            'debug' => true,
            'cache' => false,
            'autoescape' => false,
        ]);

        $d1 = new DateTime();
        $d2 = new DateTime();
        $output = $twig->render('index', compact('d1', 'd2'));

        // If it fails, PHP will crash.
        $this->assertEquals($output, $d1->date.$d2->date);
    }
}
