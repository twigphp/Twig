<?php

namespace Twig\Sandbox;

require __DIR__.'/../../lib/Twig/Sandbox/SecurityNotAllowedFilterError.php';

if (\false) {
    class SecurityNotAllowedFilterError extends \Twig_Sandbox_SecurityNotAllowedFilterError
    {
    }
}
