<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Util;

/**
 * @author Fabien Potencier <fabien@symfony.com>
 */
class TemplateDirIterator extends \IteratorIterator
{
    /**
     * @return false|mixed|string
     */
    public function current()
    {
        return file_get_contents(parent::current());
    }

    /**
     * @return bool|float|int|string|null
     */
    public function key()
    {
        return (string) parent::key();
    }
}
