<?php

namespace Twig\Sandbox;

require_once __DIR__.'/../../lib/Twig/Sandbox/SecurityNotAllowedFunctionError.php';

if (\false) {
    class SecurityNotAllowedFunctionError extends \Twig_Sandbox_SecurityNotAllowedFunctionError
    {
    }
}
