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
 * Interface for custom attribute value objects.
 *
 * Implement this interface to create custom conversion logic when printing out objects as attribute
 * values in the `html_attr` function.
 *
 * @author Matthias Pigulla <mp@webfactory.de>
 */
interface AttributeValueInterface
{
    /**
     * Returns the string representation of the attribute value. The returned value
     * will automatically be escaped for the HTML attribute context.
     *
     * @return string|null the attribute value as a string, or null to omit the attribute
     */
    public function getValue(): ?string;
}
