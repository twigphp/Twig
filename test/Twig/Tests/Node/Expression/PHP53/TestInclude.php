<?php

$env = new Twig_Environment(new Twig_Loader_Array([]));
$env->addTest(new Twig_SimpleTest('anonymous', function () {}));

return $env;
