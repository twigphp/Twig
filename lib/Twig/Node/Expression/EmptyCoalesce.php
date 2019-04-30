<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Twig\Node\Expression\EmptyCoalesceExpression;

@trigger_error(sprintf('Using the "Twig_Node_Expression_EmptyCoalesce" class is deprecated since Twig version 2.7, use "Twig\Node\Expression\EmptyCoalesceExpression" instead.'), E_USER_DEPRECATED);

class_exists('Twig\Node\Expression\EmptyCoalesceExpression');

if (\false) {
    class Twig_Node_Expression_EmptyCoalesce extends EmptyCoalesceExpression
    {
    }
}
