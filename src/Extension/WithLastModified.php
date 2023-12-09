<?php

namespace Twig\Extension;

/**
 * Freshness of templates use the last modification date of each extension class.
 * Implement this interface to provide a different last modification date.
 */
interface WithLastModified
{
    /**
     * @return int A UNIX timestamp
     */
    public function getLastModified(): int;
}
