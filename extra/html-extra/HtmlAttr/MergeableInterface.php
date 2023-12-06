<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Extra\Html\HtmlAttr;

/**
 * Interface for attribute values that support custom merge behavior.
 *
 * Implement this interface to define how attribute values should be merged when multiple
 * attribute arrays are combined using `html_attr_merge`. This allows for
 * sophisticated merging logic beyond simple array merging.
 *
 * Objects implementing this interface probably want to implement either {@see AttributeValueInterface}
 * or `\Stringable` as well, in order to provide a reasonable to-string-conversion when being
 * the last value after a merge.
 *
 * @author Matthias Pigulla <mp@webfactory.de>
 */
interface MergeableInterface
{
    /**
     * Merge value from $this with another, previous value. In the merge arguments list, $this is on the right
     * hand side of $previous. This is the preferred merge method, so that newer (right) values have control
     * over the result.
     *
     * The `$previous` value is whatever value was present for a particular attribute before. Implementations
     * are free to completely ignore this value, effectively implementing "override only" merge behavior.
     * They can merge it with their own value, resulting in array-like merge behavior. Or they could throw
     * an exception when only particular types can be merged.
     *
     * Returns the new, resulting value as a new instance. Does not modify either $this not $previous.
     */
    public function mergeInto(mixed $previous): mixed;

    /**
     * Merge value from $this with a new value. In the merge arguments list, $this is on the left hand side of
     * $newValue, but $newValue does not implement this interface itself.
     *
     * The `$newValue` value is whatever was given in the right hand side merge argument. Implementations are
     * free to return either the new value, implementing "override" merge behavior. They could also return
     * a value that represents a merge result between their current and the new value. Or they could throw
     * an exception when only particular types can be merged.
     *
     * Returns the new, resulting set as a new instance. Does not modify $this.
     */
    public function appendFrom(mixed $newValue): mixed;
}
