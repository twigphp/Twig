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

use PHPUnit\Framework\TestCase;
use Twig\FileExtensionEscapingStrategy;

class FileExtensionEscapingStrategyTest extends TestCase
{
    /**
     * @dataProvider getGuessData
     * @param $strategy
     * @param $filename
     */
    public function testGuess($strategy, $filename)
    {
        $this->assertSame($strategy, FileExtensionEscapingStrategy::guess($filename));
    }

    public function getGuessData()
    {
        return [
            // default
            ['html', 'foo.html'],
            ['html', 'foo.html.twig'],
            ['html', 'foo'],
            ['html', 'foo.bar.twig'],
            ['html', 'foo.txt/foo'],
            ['html', 'foo.txt/foo.js/'],

            // css
            ['css', 'foo.css'],
            ['css', 'foo.css.twig'],
            ['css', 'foo.twig.css'],
            ['css', 'foo.js.css'],
            ['css', 'foo.js.css.twig'],

            // js
            ['js', 'foo.js'],
            ['js', 'foo.js.twig'],
            ['js', 'foo.txt/foo.js'],
            ['js', 'foo.txt.twig/foo.js'],

            // txt
            [false, 'foo.txt'],
            [false, 'foo.txt.twig'],
        ];
    }
}
