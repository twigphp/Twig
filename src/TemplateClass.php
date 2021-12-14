<?php

namespace Twig;

/**
 * Template name with its pre-generated class name.
 *
 * @internal
 */
class TemplateClass
{
    /**
     * @readonly
     */
    public $name;

    /**
     * @readonly
     */
    public $class;

    /**
     * @param string $class
     * @param string $name
     */
    public function __construct($class, $name)
    {
        $this->class = $class;
        $this->name = $name;
    }
}
