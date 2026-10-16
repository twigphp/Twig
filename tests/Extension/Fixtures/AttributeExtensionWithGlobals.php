<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Tests\Extension\Fixtures;

use Twig\Extension\AttributeExtension;
use Twig\Extension\GlobalsInterface;

class AttributeExtensionWithGlobals extends AttributeExtension implements GlobalsInterface
{
    public function __construct()
    {
        // No parent call: the class to read the attributes from is static::class
    }
}
