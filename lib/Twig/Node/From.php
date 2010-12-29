<?php

/*
 * This file is part of Twig.
 *
 * (c) 2010 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Represents a from node.
 *
 * @package    twig
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 */
class Twig_Node_From extends Twig_Node_Import
{
    public function __construct(Twig_Node_Expression $expr, $lineno, $tag = null)
    {
        parent::__construct($expr, new Twig_Node_Expression_AssignName(str_replace('.', '_', uniqid('_imported_', true)), $lineno), $lineno, $tag);
    }
}
