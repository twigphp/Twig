<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Extension;

/**
 * Allows objects to declare whether they should be considered empty.
 *
 * @author Brandon Kelly <brandon@pixelandtonic.com>
 * @since 3.24.1
 */
interface EmptyInterface
{
    /**
     * @return bool
     */
    public function getIsEmpty(): bool;
}
