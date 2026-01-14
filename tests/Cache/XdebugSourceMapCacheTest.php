<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Tests\Cache;

use PHPUnit\Framework\TestCase;
use Twig\Cache\FilesystemCache;
use Twig\Cache\XdebugSourceMapCache;
use Twig\Tests\FilesystemHelper;

/**
 * @requires function xdebug_set_source_map
 */
class XdebugSourceMapCacheTest extends TestCase
{
    private string $directory;
    private string $mapDirectory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir().'/twig-xdebug-test';
        $this->mapDirectory = $this->directory.'/.xdebug';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->directory)) {
            FilesystemHelper::removeDir($this->directory);
        }
    }

    public function testWriteCreatesSourceMap()
    {
        $innerCache = new FilesystemCache($this->directory);
        $cache = new XdebugSourceMapCache($innerCache);

        $key = $cache->generateKey('test.twig', 'TestClass');
        $templatePath = '/path/to/templates/test.twig';
        $debugInfo = [10 => 1, 20 => 5, 30 => 10];

        $cache->setSourceMapData($templatePath, $debugInfo);
        $cache->write($key, '<?php class TestClass {}');

        $mapFile = $this->mapDirectory.'/'.pathinfo($key, \PATHINFO_FILENAME).'.map';
        $this->assertFileExists($mapFile);

        $content = file_get_contents($mapFile);
        $this->assertStringContainsString('# Xdebug source map for Twig template', $content);
        $this->assertStringContainsString('remote_prefix: '.\dirname($key).'/', $content);
        $this->assertStringContainsString('local_prefix: /path/to/templates/', $content);
        $compiledFile = basename($key);
        $this->assertStringContainsString($compiledFile.':10-19 = test.twig:1', $content);
        $this->assertStringContainsString($compiledFile.':20-29 = test.twig:5', $content);
        $this->assertStringContainsString($compiledFile.':30-999999 = test.twig:10', $content);
    }

    public function testWriteWithoutSourceMapDataDoesNotCreateMapFile()
    {
        $innerCache = new FilesystemCache($this->directory);
        $cache = new XdebugSourceMapCache($innerCache);

        $key = $cache->generateKey('test.twig', 'TestClass');
        $cache->write($key, '<?php class TestClass {}');

        $mapFile = $this->mapDirectory.'/'.pathinfo($key, \PATHINFO_FILENAME).'.map';
        $this->assertFileDoesNotExist($mapFile);
    }

    public function testWriteClearsPendingDataAfterWrite()
    {
        $innerCache = new FilesystemCache($this->directory);
        $cache = new XdebugSourceMapCache($innerCache);

        $key1 = $cache->generateKey('test1.twig', 'TestClass1');
        $key2 = $cache->generateKey('test2.twig', 'TestClass2');

        $cache->setSourceMapData('/path/test1.twig', [10 => 1]);
        $cache->write($key1, '<?php class TestClass1 {}');

        // Second write without setting source map data should not create a map
        $cache->write($key2, '<?php class TestClass2 {}');

        $mapFile1 = $this->mapDirectory.'/'.pathinfo($key1, \PATHINFO_FILENAME).'.map';
        $mapFile2 = $this->mapDirectory.'/'.pathinfo($key2, \PATHINFO_FILENAME).'.map';

        $this->assertFileExists($mapFile1);
        $this->assertFileDoesNotExist($mapFile2);
    }

    public function testRemoveDeletesSourceMap()
    {
        $innerCache = new FilesystemCache($this->directory);
        $cache = new XdebugSourceMapCache($innerCache);

        $key = $cache->generateKey('test.twig', 'TestClass');
        $cache->setSourceMapData('/path/test.twig', [10 => 1]);
        $cache->write($key, '<?php class TestClass {}');

        $mapFile = $this->mapDirectory.'/'.pathinfo($key, \PATHINFO_FILENAME).'.map';
        $this->assertFileExists($mapFile);

        $cache->remove('test.twig', 'TestClass');

        $this->assertFileDoesNotExist($mapFile);
    }

    public function testDelegatesGenerateKey()
    {
        $innerCache = new FilesystemCache($this->directory);
        $cache = new XdebugSourceMapCache($innerCache);

        $expected = $innerCache->generateKey('test.twig', 'TestClass');
        $this->assertSame($expected, $cache->generateKey('test.twig', 'TestClass'));
    }

    public function testDelegatesGetTimestamp()
    {
        $innerCache = new FilesystemCache($this->directory);
        $cache = new XdebugSourceMapCache($innerCache);

        $key = $cache->generateKey('test.twig', 'TestClass');
        $cache->write($key, '<?php class TestClass {}');

        $this->assertSame($innerCache->getTimestamp($key), $cache->getTimestamp($key));
    }

    public function testDelegatesLoad()
    {
        $nonce = hash(\PHP_VERSION_ID < 80100 ? 'sha256' : 'xxh128', random_bytes(32));
        $className = '__Twig_Tests_Cache_XdebugSourceMapCacheTest_'.$nonce;

        $innerCache = new FilesystemCache($this->directory);
        $cache = new XdebugSourceMapCache($innerCache);

        $key = $cache->generateKey('test.twig', $className);
        $cache->write($key, '<?php class '.$className.' {}');

        $this->assertFalse(class_exists($className, false));
        $cache->load($key);
        $this->assertTrue(class_exists($className, false));
    }
}
