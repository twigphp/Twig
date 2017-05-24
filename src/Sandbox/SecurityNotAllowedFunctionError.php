<?php

namespace Twig\Sandbox;

require __DIR__.'/../../lib/Twig/Sandbox/SecurityNotAllowedFunctionError.php';

if (\false) {
    class SecurityNotAllowedFunctionError extends \Twig_Sandbox_SecurityNotAllowedFunctionError
    {
    }
}
