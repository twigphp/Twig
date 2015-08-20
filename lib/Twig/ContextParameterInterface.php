<?php

/**
 * For implementing when an additional logic is needed before
 * pass the template context parameter to the template.
 *
 * @author Vladimir Balin <krocos@mail.ru>
 */
interface ContextParameterInterface
{
    /**
     * @return mixed The prepared template context parameter
     */
    public function prepare();
}
