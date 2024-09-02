<?php

use Symfony\Component\ErrorHandler\ErrorHandler;

require __DIR__ . '/vendor/autoload.php';

// see https://github.com/symfony/symfony/issues/53812#issuecomment-1962740145
set_exception_handler([new ErrorHandler(), 'handleException']);
