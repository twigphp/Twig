<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Sandbox;

/**
 * Exception thrown when a not allowed tag is used in a template.
 *
 * @author Martin Hasoň <martin.hason@gmail.com>
 */
final class SecurityNotAllowedTagError extends SecurityError
{
    /**
     * @var string
     */
    private $tagName;

    /**
     * SecurityNotAllowedTagError constructor.
     * @param string $message
     * @param string $tagName
     */
    public function __construct(string $message, string $tagName)
    {
        parent::__construct($message);
        $this->tagName = $tagName;
    }

    /**
     * @return string
     */
    public function getTagName(): string
    {
        return $this->tagName;
    }
}
