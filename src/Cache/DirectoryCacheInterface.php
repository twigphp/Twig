<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Cache;

/**
 * Interface for caches that store files in directories.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
interface DirectoryCacheInterface
{
    /**
     * @return string[]
     */
    public function getDirectories(): array;
}
