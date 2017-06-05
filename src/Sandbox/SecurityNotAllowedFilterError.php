<?php

namespace Twig\Sandbox;

require_once __DIR__.'/../../lib/Twig/Sandbox/SecurityNotAllowedFilterError.php';

if (\false) {
    class SecurityNotAllowedFilterError extends \Twig_Sandbox_SecurityNotAllowedFilterError
    {
    }
}
