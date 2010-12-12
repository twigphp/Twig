<?php

class Twig_Tests_FileCachingTest extends PHPUnit_Framework_TestCase
{
    protected $fileName;
    protected $tmpDir;

    function setUp()
    {
        $this->tmpDir = sys_get_temp_dir();
        if (!is_writable($this->tmpDir)) {
            $this->markTestSkipped(sprintf('Cannot write to %s, cannot test file caching.', $this->tmpDir));
        }
        parent::setUp();
    }
    
    function testWritingCacheFiles()
    {
        $loader = new Twig_Loader_String();
        $env = new Twig_Environment($loader, array('cache' => $this->tmpDir));

        $name = 'This is just text.';
        $template = $env->loadTemplate($name);
        $cacheFileName = $env->getCacheFilename($name);

        $this->assertTrue(file_exists($cacheFileName), 'Cache file does not exist.');
        $this->fileName = $cacheFileName;
    }

    function tearDown()
    {
        if($this->fileName) {
            unlink($this->fileName);
        }
        parent::tearDown();
    }
}