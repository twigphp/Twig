<?php
require dirname(__FILE__).'/Escaping.php';

/*
 * This file is part of Twig.
 *
 * (c) 2009 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Twig_OutputEscaping_Escaper
{
    /**
     * @param $string
     * @param string $strategy
     * @param string $charset
     * @return string
     */
    public function escape($string, $strategy = 'html', $charset = 'UTF-8')
    {
        return twig_escape($string, $strategy, $charset);
    }
} 