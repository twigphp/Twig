<?php

/*
 * This file is part of Twig.
 *
 * (c) 2012 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Represents a callable template function.
 *
 * Use Twig_SimpleFunction instead.
 *
 * @package    twig
 * @author     Fabien Potencier <fabien@symfony.com>
 * @deprecated
 */
interface Twig_FunctionCallableInterface
{
    public function getCallable();
}
