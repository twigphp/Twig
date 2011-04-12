<?php

require_once dirname(__FILE__).'/TestCase.php';

class Twig_Tests_FileCachingTest extends Twig_Tests_TestCase
{
    protected $fileName;
    protected $env;

    public function setUp()
    {
        parent::setUp();

        $this->env = new Twig_Environment(new Twig_Loader_String(), array('cache' => $this->tmpDir));
    }

    public function tearDown()
    {
        if ($this->fileName) {
            unlink($this->fileName);
        }

        parent::tearDown();
    }

    public function testWritingCacheFiles()
    {
        $name = 'This is just text.';
        $template = $this->env->loadTemplate($name);
        $cacheFileName = $this->env->getCacheFilename($name);

        $this->assertTrue(file_exists($cacheFileName), 'Cache file does not exist.');
        $this->fileName = $cacheFileName;
    }

    public function testClearingCacheFiles()
    {
        $name = 'I will be deleted.';
        $template = $this->env->loadTemplate($name);
        $cacheFileName = $this->env->getCacheFilename($name);

        $this->assertTrue(file_exists($cacheFileName), 'Cache file does not exist.');
        $this->env->clearCacheFiles();
        $this->assertFalse(file_exists($cacheFileName), 'Cache file was not cleared.');
    }
}
