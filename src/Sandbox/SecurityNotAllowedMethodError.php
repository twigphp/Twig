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
 * Exception thrown when a not allowed class method is used in a template.
 *
 * @author Kit Burton-Senior <mail@kitbs.com>
 */
final class SecurityNotAllowedMethodError extends SecurityError
{
    /**
     * @var string
     */
    private $className;
    /**
     * @var string
     */
    private $methodName;

    /**
     * SecurityNotAllowedMethodError constructor.
     * @param string $message
     * @param string $className
     * @param string $methodName
     */
    public function __construct(string $message, string $className, string $methodName)
    {
        parent::__construct($message);
        $this->className = $className;
        $this->methodName = $methodName;
    }

    /**
     * @return string
     */
    public function getClassName(): string
    {
        return $this->className;
    }

    /**
     * @return string
     */
    public function getMethodName()
    {
        return $this->methodName;
    }
}
