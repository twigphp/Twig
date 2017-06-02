<?php

namespace Twig\Sandbox;

require_once __DIR__.'/../../lib/Twig/Sandbox/SecurityNotAllowedPropertyError.php';

if (\false) {
    class SecurityNotAllowedPropertyError extends \Twig_Sandbox_SecurityNotAllowedPropertyError
    {
    }
}
