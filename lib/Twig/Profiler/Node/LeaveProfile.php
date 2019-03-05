<?php

use Twig\Profiler\Node\LeaveProfileNode;

class_exists('Twig\Profiler\Node\LeaveProfileNode');

@trigger_error(sprintf('Using the "Twig_Profiler_Node_LeaveProfile" class is deprecated since Twig version 1.38, use "Twig\Profiler\Node\LeaveProfileNode" instead.'), E_USER_DEPRECATED);

if (\false) {
    class Twig_Profiler_Node_LeaveProfile extends LeaveProfileNode
    {
    }
}
