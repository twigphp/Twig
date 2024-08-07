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
use Twig\Cache\ReadOnlyFilesystemCache;
use Twig\Tests\FilesystemHelper;

class ReadOnlyFilesystemTest extends TestCase
{
    private $classname;
    private $directory;
    private $cache;

    protected function setUp(): void
    {
        $nonce = hash(\PHP_VERSION_ID < 80100 ? 'sha256' : 'xxh128', random_bytes(32));
        $this->classname = '__Twig_Tests_Cache_ReadOnlyFilesystemTest_Template_'.$nonce;
        $this->directory = sys_get_temp_dir().'/twig-test';
        $this->cache = new ReadOnlyFilesystemCache($this->directory);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->directory)) {
            FilesystemHelper::removeDir($this->directory);
        }
    }

    public function testLoad()
    {
        $key = $this->directory.'/cache/ro-cachefile.php';

        $dir = \dirname($key);
        @mkdir($dir, 0777, true);
        $this->assertDirectoryExists($dir);
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

        $this->assertFileDoesNotExist($key);
        $this->assertFileDoesNotExist($this->directory);

        $this->cache->write($key, $content);

        $this->assertFileDoesNotExist($this->directory);
        $this->assertFileDoesNotExist($key);
    }

    public function testGetTimestamp()
    {
        $key = $this->directory.'/cache/cachefile.php';

        $dir = \dirname($key);
        @mkdir($dir, 0777, true);
        $this->assertDirectoryExists($dir);

        // Create the file with a specific modification time.
        touch($key, 1234567890);

        $this->assertSame(1234567890, $this->cache->getTimestamp($key));
    }

    public function testGetTimestampMissingFile()
    {
        $key = $this->directory.'/cache/cachefile.php';
        $this->assertSame(0, $this->cache->getTimestamp($key));
    }

    /**
     * Test file cache is tolerant towards trailing (back)slashes on the configured cache directory.
     *
     * @dataProvider provideDirectories
     */
    public function testGenerateKey($expected, $input)
    {
        $cache = new ReadOnlyFilesystemCache($input);
        $this->assertMatchesRegularExpression($expected, $cache->generateKey('_test_', static::class));
    }

    public static function provideDirectories()
    {
        $pattern = '#a/b/[a-zA-Z0-9]+/[a-zA-Z0-9]+.php$#';

        return [
            [$pattern, 'a/b'],
            [$pattern, 'a/b/'],
            [$pattern, 'a/b\\'],
            [$pattern, 'a/b\\/'],
            [$pattern, 'a/b\\//'],
            ['#/'.substr($pattern, 1), '/a/b'],
        ];
    }

    private function generateSource()
    {
        return strtr('<?php class {{classname}} {}', [
            '{{classname}}' => $this->classname,
        ]);
    }
}
