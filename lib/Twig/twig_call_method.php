<?php

/**
 * @internal
 */
function twig_call_method($object, $method, $arguments)
{
    return $object->$method(...$arguments);
}
