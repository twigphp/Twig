<?php

namespace Twig\Sandbox;

require __DIR__.'/../../lib/Twig/Sandbox/SecurityNotAllowedMethodError.php';

if (\false) {
    class SecurityNotAllowedMethodError extends \Twig_Sandbox_SecurityNotAllowedMethodError
    {
    }
}
