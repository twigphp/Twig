<?php

/**
 * create_phar_packages.php by Vladislav "FractalizeR" Rastrusny
 *
 * Running this script will create Twig phar packages in all formats (twig.phar[.XXX], available at
 * your system in the root directory of development folder
 *
 * You can run Twig from generated phars by just requiring it. Test it now with the following code:
 *
 * <?php
 * require_once("twig.phar");
 *
 * $loader = new Twig_Loader_Array(array('hello' => 'Hello, world!'));
 * $twig = new Twig_Environment($loader, array());
 * $template = $twig->loadTemplate('hello');
 * echo $template->render(array());
 */

if(!extension_loaded('Phar')) {
    die("Phar extension is not loaded!");
}

createPhar(__DIR__ . "/../twig.phar");

if (extension_loaded('zlib')) {
    createPhar(__DIR__ . "/../twig.phar.gz", Phar::GZ);
}

if (extension_loaded('bz2')) {
    createPhar(__DIR__ . "/../twig.phar.bz2", Phar::BZ2);
}

function createPhar($pharFilename, $compression = false) {
    @unlink($pharFilename);
    $phar = new Phar($pharFilename, null, 'twig');

    if ($compression) {
        $phar->compress($compression);
    }

    $stub = "<?php
require_once('Twig/Autoloader.php');
Twig_Autoloader::register();
__HALT_COMPILER();";

    $phar->setStub($stub);
    $phar->buildFromDirectory(__DIR__ . '/../lib');
}