<?php

namespace Twig\Sandbox;

require __DIR__.'/../../lib/Twig/Sandbox/SecurityNotAllowedPropertyError.php';

if (\false) {
    class SecurityNotAllowedPropertyError extends \Twig_Sandbox_SecurityNotAllowedPropertyError
    {
    }
}
