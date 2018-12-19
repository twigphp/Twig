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

/**
 * @author    nystudio107
 * @package   EmptyCoalesce
 * @since     1.0.0
 *
 */
class Twig_Tests_Node_Expression_EmptyCoalesceTest extends Twig_Test_NodeTestCase
{
    public function getTests()
    {
        $left = new Twig_Node_Expression_Name('foo', 1);
        $right = new Twig_Node_Expression_Constant(2, 1);
        $node = new Twig_Node_Expression_EmptyCoalesce($left, $right, 1);

        return array(array($node, "((// line 1\n\((empty((\$context[\"foo\"] ?? null)) ? null : (\$context[\"foo\"] ?? null)) ?? (empty(2) ? null : 2))"));
    }
}
