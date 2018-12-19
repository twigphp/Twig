<?php
/**
 * Empty Coalesce plugin for Craft CMS 3.x
 *
 * Empty Coalesce adds the ??? operator to Twig that will return the first thing
 * that is defined, not null, and not empty.
 *
 * @link      https://nystudio107.com/
 * @copyright Copyright (c) 2018 nystudio107
 */

namespace nystudio107\emptycoalesce\Node\Expression;

/**
 * @author    nystudio107
 * @package   EmptyCoalesce
 * @since     1.0.0
 */
class_exists('Twig_Node_Expression_EmptyCoalesce');

if (\false) {
    class EmptyCoalesceExpression extends \Twig_Node_Expression_EmptyCoalesce
    {
    }
}
