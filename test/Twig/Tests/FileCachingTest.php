<?php

class Twig_Tests_FileCachingTest extends PHPUnit_Framework_TestCase
{
    protected $fileName;
    protected $tmpDir;
    protected $env;

    function setUp()
    {
        $this->tmpDir = sys_get_temp_dir().'/TwigCache';
        if (!file_exists($this->tmpDir)) {
            @mkdir($this->tmpDir, 0777, true);;
        }

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
        if ($this->fileName) {
            unlink($this->fileName);
        }
        $this->removeDir($this->tmpDir);
        parent::tearDown();
    }

    private function removeDir($target)
    {
        $fp = opendir($target);
        while (false !== $file = readdir($fp)) {
            if (in_array($file, array('.', '..'))) {
                continue;
            }

            if (is_dir($target.'/'.$file)) {
                self::removeDir($target.'/'.$file);
            } else {
                unlink($target.'/'.$file);
            }
        }
        closedir($fp);
        rmdir($target);
    }
}
