<?php

class ThemeTest extends \PHPUnit_Framework_TestCase
{
    public function testThemeAndBlock()
    {
        $loader = new Twig_Loader_Filesystem(array());
        $loader->addPath(__DIR__.'/Fixtures/themes/theme2');
        $loader->addPath(__DIR__.'/Fixtures/themes/theme1');
        $loader->addPath(__DIR__.'/Fixtures/themes/theme1', 'default_theme');

        $twig = new Twig_Environment($loader);

        $template = $twig->loadTemplate('blocks.html.twig');
        $this->assertSame('block from theme 1', $template->renderBlock('b1', array()));

        $template = $twig->loadTemplate('blocks.html.twig');
        $this->assertSame('block from theme 2', $template->renderBlock('b2', array()));
    }
}
