<?php

class Twig_Tests_FileCachingTest extends PHPUnit_Framework_TestCase
{
    protected $fileName;
    protected $tmpDir;
    protected $env;

    function setUp()
    {
        $tmpDir = sys_get_temp_dir();
        $this->tmpDir = $tmpDir . DIRECTORY_SEPARATOR . 'TwigCache';
        @mkdir($this->tmpDir, 0777, true);
        if (!is_writable($this->tmpDir)) {
            $this->markTestSkipped(sprintf('Cannot write to %s, cannot test file caching.', $this->tmpDir));
        }
        $this->env = new Twig_Environment(new Twig_Loader_String(), array('cache' => $this->tmpDir));
        parent::setUp();
    }
    
    function testWritingCacheFiles()
    {
        $name = 'This is just text.';
        $template = $this->env->loadTemplate($name);
        $cacheFileName = $this->env->getCacheFilename($name);

        $this->assertTrue(file_exists($cacheFileName), 'Cache file does not exist.');
        $this->fileName = $cacheFileName;
    }

    function testClearingCacheFiles()
    {
        $name = 'I will be deleted.';
        $template = $this->env->loadTemplate($name);
        $cacheFileName = $this->env->getCacheFilename($name);

        $this->assertTrue(file_exists($cacheFileName), 'Cache file does not exist.');
        $this->env->clearCacheFiles();
        $this->assertFalse(file_exists($cacheFileName), 'Cache file was not cleared.');
    }

    function tearDown()
    {
        if($this->fileName) {
            unlink($this->fileName);
        }
        passthru("rm -r \"{$this->tmpDir}\"");
        parent::tearDown();
    }
}
