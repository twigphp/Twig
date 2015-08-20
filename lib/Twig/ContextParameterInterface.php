<?php

/**
 * To be implemented when an additional logic is required before
 * passing the context parameter to the template.
 *
 * @author Vladimir Balin <krocos@mail.ru>
 */
interface Twig_ContextParameterInterface
{
    /**
     * @return mixed The prepared template context parameter
     */
    public function prepare();
}
