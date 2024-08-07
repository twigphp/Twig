<?php

namespace Twig\Tests\Cache;

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use PHPUnit\Framework\TestCase;
use Twig\Cache\ChainCache;
use Twig\Cache\FilesystemCache;
use Twig\Tests\FilesystemHelper;

class ChainTest extends TestCase
{
    private $classname;
    private $directory;
    private $cache;
    private $key;

    protected function setUp(): void
    {
        $nonce = hash(\PHP_VERSION_ID < 80100 ? 'sha256' : 'xxh128', random_bytes(32));
        $this->classname = '__Twig_Tests_Cache_ChainTest_Template_'.$nonce;
        $this->directory = sys_get_temp_dir().'/twig-test';
        $this->cache = new ChainCache([
            new FilesystemCache($this->directory.'/A'),
            new FilesystemCache($this->directory.'/B'),
        ]);
        $this->key = $this->cache->generateKey('_test_', $this->classname);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->directory)) {
            FilesystemHelper::removeDir($this->directory);
        }
    }

    public function testLoadInA()
    {
        $cache = new FilesystemCache($this->directory.'/A');
        $key = $cache->generateKey('_test_', $this->classname);

        $dir = \dirname($key);
        @mkdir($dir, 0777, true);
        $this->assertDirectoryExists($dir);
        $this->assertFalse(class_exists($this->classname, false));

        $content = $this->generateSource();
        file_put_contents($key, $content);

        $this->cache->load($this->key);

        $this->assertTrue(class_exists($this->classname, false));
    }

    public function testLoadInB()
    {
        $cache = new FilesystemCache($this->directory.'/B');
        $key = $cache->generateKey('_test_', $this->classname);

        $dir = \dirname($key);
        @mkdir($dir, 0777, true);
        $this->assertDirectoryExists($dir);
        $this->assertFalse(class_exists($this->classname, false));

        $content = $this->generateSource();
        file_put_contents($key, $content);

        $this->cache->load($this->key);

        $this->assertTrue(class_exists($this->classname, false));
    }

    public function testLoadInBoth()
    {
        $cache = new FilesystemCache($this->directory.'/A');
        $key = $cache->generateKey('_test_', $this->classname);

        $dir = \dirname($key);
        @mkdir($dir, 0777, true);
        $this->assertDirectoryExists($dir);
        $this->assertFalse(class_exists($this->classname, false));

        $content = $this->generateSource();
        file_put_contents($key, $content);

        $cache = new FilesystemCache($this->directory.'/B');
        $key = $cache->generateKey('_test_', $this->classname);

        $dir = \dirname($key);
        @mkdir($dir, 0777, true);
        $this->assertDirectoryExists($dir);
        $this->assertFalse(class_exists($this->classname, false));

        $content = $this->generateSource();
        file_put_contents($key, $content);

        $this->cache->load($this->key);

        $this->assertTrue(class_exists($this->classname, false));
    }

    public function testLoadMissing()
    {
        $this->assertFalse(class_exists($this->classname, false));

        $this->cache->load($this->key);

        $this->assertFalse(class_exists($this->classname, false));
    }

    public function testWrite()
    {
        $content = $this->generateSource();

        $cacheA = new FilesystemCache($this->directory.'/A');
        $keyA = $cacheA->generateKey('_test_', $this->classname);

        $this->assertFileDoesNotExist($keyA);
        $this->assertFileDoesNotExist($this->directory.'/A');

        $cacheB = new FilesystemCache($this->directory.'/B');
        $keyB = $cacheB->generateKey('_test_', $this->classname);

        $this->assertFileDoesNotExist($keyB);
        $this->assertFileDoesNotExist($this->directory.'/B');

        $this->cache->write($this->key, $content);

        $this->assertFileExists($this->directory.'/A');
        $this->assertFileExists($keyA);
        $this->assertSame(file_get_contents($keyA), $content);

        $this->assertFileExists($this->directory.'/B');
        $this->assertFileExists($keyB);
        $this->assertSame(file_get_contents($keyB), $content);
    }

    public function testGetTimestampInA()
    {
        $cache = new FilesystemCache($this->directory.'/A');
        $key = $cache->generateKey('_test_', $this->classname);

        $dir = \dirname($key);
        @mkdir($dir, 0777, true);
        $this->assertDirectoryExists($dir);

        // Create the file with a specific modification time.
        touch($key, 1234567890);

        $this->assertSame(1234567890, $this->cache->getTimestamp($this->key));
    }

    public function testGetTimestampInB()
    {
        $cache = new FilesystemCache($this->directory.'/B');
        $key = $cache->generateKey('_test_', $this->classname);

        $dir = \dirname($key);
        @mkdir($dir, 0777, true);
        $this->assertDirectoryExists($dir);

        // Create the file with a specific modification time.
        touch($key, 1234567890);

        $this->assertSame(1234567890, $this->cache->getTimestamp($this->key));
    }

    public function testGetTimestampInBoth()
    {
        $cacheA = new FilesystemCache($this->directory.'/A');
        $keyA = $cacheA->generateKey('_test_', $this->classname);

        $dir = \dirname($keyA);
        @mkdir($dir, 0777, true);
        $this->assertDirectoryExists($dir);

        // Create the file with a specific modification time.
        touch($keyA, 1234567890);

        $cacheB = new FilesystemCache($this->directory.'/B');
        $keyB = $cacheB->generateKey('_test_', $this->classname);

        $dir = \dirname($keyB);
        @mkdir($dir, 0777, true);
        $this->assertDirectoryExists($dir);

        // Create the file with a specific modification time.
        touch($keyB, 1234567891);

        $this->assertSame(1234567890, $this->cache->getTimestamp($this->key));
    }

    public function testGetTimestampMissingFile()
    {
        $this->assertSame(0, $this->cache->getTimestamp($this->key));
    }

    /**
     * @dataProvider provideInput
     */
    public function testGenerateKey($expected, $input)
    {
        $cache = new ChainCache([]);
        $this->assertSame($expected, $cache->generateKey($input, static::class));
    }

    public static function provideInput()
    {
        return [
            ['Twig\Tests\Cache\ChainTest#_test_', '_test_'],
            ['Twig\Tests\Cache\ChainTest#_test#with#hashtag_', '_test#with#hashtag_'],
        ];
    }

    private function generateSource()
    {
        return strtr('<?php class {{classname}} {}', [
            '{{classname}}' => $this->classname,
        ]);
    }
}
