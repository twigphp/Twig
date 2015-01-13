<?php

/*
 * This file is part of Twig.
 *
 * (c) 2015 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Default autoescaping strategy based on file names.
 *
 * This strategy sets the HTML as the default autoescaping strategy,
 * but changes it based on the filename.
 *
 * Note that there is no runtime performance impact as the
 * default autoescaping strategy is set at compilation time.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Twig_FileExtensionEscapingStrategy
{
    /**
     * Guesses the best autoescaping strategy based on the file name.
     *
     * @param string $filename The template file name
     *
     * @return string The escaping strategy name to use
     */
    public static function guess($filename)
    {
        if (!preg_match('{\.(js|css|txt)(?:\.[^/\\\\]+)?$}', $filename, $match)) {
            return 'html';
        }

        switch ($match[1]) {
            case 'js':
                return 'js';

            case 'css':
                return 'css';

            case 'txt':
                return false;
        }
    }
}
