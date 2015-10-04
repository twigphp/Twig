<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Twig_Tests_Cache_FilesystemTest extends PHPUnit_Framework_TestCase
{
    private $nonce;
    private $classname;
    private $directory;
    private $cache;

    protected function setUp()
    {
        $this->nonce = hash('sha256', uniqid(mt_rand(), true));
        $this->classname = '__Twig_Tests_Cache_FilesystemTest_Template_'.$this->nonce;
        $this->directory = sys_get_temp_dir().'/twig-test-'.$this->nonce;
        $this->cache = new Twig_Cache_Filesystem($this->directory);
    }

    protected function tearDown()
    {
        if (file_exists($this->directory)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->directory), RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($iterator as $filename => $fileInfo) {
                if (!$iterator->isDot()) {
                    if ($fileInfo->isDir()) {
                        rmdir($filename);
                    } else {
                        unlink($filename);
                    }
                }
            }
            rmdir($this->directory);
        }
    }

    public function testLoad()
    {
        $key = $this->directory.'/cache/cachefile.php';

        $dir = dirname($key);
        @mkdir($dir, 0777, true);
        $this->assertTrue(is_dir($dir));
        $this->assertFalse(class_exists($this->classname, false));

        $content = $this->generateSource();
        file_put_contents($key, $content);

        $this->cache->load($key);

        $this->assertTrue(class_exists($this->classname, false));
    }

    public function testLoadMissing()
    {
        $key = $this->directory.'/cache/cachefile.php';

        $this->assertFalse(class_exists($this->classname, false));

        $this->cache->load($key);

        $this->assertFalse(class_exists($this->classname, false));
    }

    public function testWrite()
    {
        $key = $this->directory.'/cache/cachefile.php';
        $content = $this->generateSource();

        $this->assertFalse(file_exists($key));
        $this->assertFalse(file_exists($this->directory));

        $this->cache->write($key, $content);

        $this->assertTrue(file_exists($this->directory));
        $this->assertTrue(file_exists($key));
        $this->assertSame(file_get_contents($key), $content);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessageRegExp #^Unable to create the cache directory #
     */
    public function testWriteFailMkdir()
    {
        $key = $this->directory.'/cache/cachefile.php';
        $content = $this->generateSource();

        $this->assertFalse(file_exists($key));

        // Create read-only root directory.
        @mkdir($this->directory, 0555, true);
        $this->assertTrue(is_dir($this->directory));

        $this->cache->write($key, $content);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessageRegExp #^Unable to write in the cache directory #
     */
    public function testWriteFailDirWritable()
    {
        $key = $this->directory.'/cache/cachefile.php';
        $content = $this->generateSource();

        $this->assertFalse(file_exists($key));

        // Create root directory.
        @mkdir($this->directory, 0777, true);
        // Create read-only subdirectory.
        @mkdir($this->directory.'/cache' , 0555);
        $this->assertTrue(is_dir($this->directory.'/cache'));

        $this->cache->write($key, $content);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessageRegExp #^Failed to write cache file #
     */
    public function testWriteFailWriteFile()
    {
        $key = $this->directory.'/cache/cachefile.php';
        $content = $this->generateSource();

        $this->assertFalse(file_exists($key));

        // Create a directory in the place of the cache file.
        @mkdir($key, 0777, true);
        $this->assertTrue(is_dir($key));

        $this->cache->write($key, $content);
    }

    public function testGetTimestamp()
    {
        $key = $this->directory.'/cache/cachefile.php';

        $dir = dirname($key);
        @mkdir($dir, 0777, true);
        $this->assertTrue(is_dir($dir));

        // Create the file with a specific modification time.
        touch($key, 1234567890);

        $this->assertSame(1234567890, $this->cache->getTimestamp($key));
    }

    public function testGetTimestampMissingFile()
    {
        $key = $this->directory.'/cache/cachefile.php';
        $this->assertSame(0, $this->cache->getTimestamp($key));
    }

    private function generateSource()
    {
        return strtr('<?php class {{classname}} {}', array(
            '{{classname}}' => $this->classname,
        ));
    }
}
