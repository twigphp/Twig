<?php

namespace Twig\Tests\Loader;

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Loader\FilesystemLoader;

class FilesystemTest extends TestCase
{
    public function testGetSourceContext()
    {
        $path = __DIR__.'/../Fixtures';
        $loader = new FilesystemLoader([$path]);
        $this->assertEquals('errors/index.html', $loader->getSourceContext('errors/index.html')->getName());
        $this->assertEquals(realpath($path.'/errors/index.html'), realpath($loader->getSourceContext('errors/index.html')->getPath()));
    }

    /**
     * @dataProvider getSecurityTests
     */
    public function testSecurity($template)
    {
        $loader = new FilesystemLoader([__DIR__.'/../Fixtures']);
        $loader->addPath(__DIR__.'/../Fixtures', 'foo');

        try {
            $loader->getCacheKey($template);
            $this->fail();
        } catch (LoaderError $e) {
            $this->assertStringNotContainsString('Unable to find template', $e->getMessage());
        }
    }

    public function getSecurityTests()
    {
        return [
            ["AutoloaderTest\0.php"],
            ['..\\AutoloaderTest.php'],
            ['..\\\\\\AutoloaderTest.php'],
            ['../AutoloaderTest.php'],
            ['..////AutoloaderTest.php'],
            ['./../AutoloaderTest.php'],
            ['.\\..\\AutoloaderTest.php'],
            ['././././././../AutoloaderTest.php'],
            ['.\\./.\\./.\\./../AutoloaderTest.php'],
            ['foo/../../AutoloaderTest.php'],
            ['foo\\..\\..\\AutoloaderTest.php'],
            ['foo/../bar/../../AutoloaderTest.php'],
            ['foo/bar/../../../AutoloaderTest.php'],
            ['filters/../../AutoloaderTest.php'],
            ['filters//..//..//AutoloaderTest.php'],
            ['filters\\..\\..\\AutoloaderTest.php'],
            ['filters\\\\..\\\\..\\\\AutoloaderTest.php'],
            ['filters\\//../\\/\\..\\AutoloaderTest.php'],
            ['/../AutoloaderTest.php'],
            ['@__main__/../AutoloaderTest.php'],
            ['@foo/../AutoloaderTest.php'],
            ['@__main__/../../AutoloaderTest.php'],
            ['@foo/../../AutoloaderTest.php'],
        ];
    }

    /**
     * @dataProvider getBasePaths
     */
    public function testPaths($basePath, $cacheKey, $rootPath)
    {
        $loader = new FilesystemLoader([$basePath.'/normal', $basePath.'/normal_bis'], $rootPath);
        $loader->setPaths([$basePath.'/named', $basePath.'/named_bis'], 'named');
        $loader->addPath($basePath.'/named_ter', 'named');
        $loader->addPath($basePath.'/normal_ter');
        $loader->prependPath($basePath.'/normal_final');
        $loader->prependPath($basePath.'/named/../named_quater', 'named');
        $loader->prependPath($basePath.'/named_final', 'named');

        $this->assertEquals([
            $basePath.'/normal_final',
            $basePath.'/normal',
            $basePath.'/normal_bis',
            $basePath.'/normal_ter',
        ], $loader->getPaths());
        $this->assertEquals([
            $basePath.'/named_final',
            $basePath.'/named/../named_quater',
            $basePath.'/named',
            $basePath.'/named_bis',
            $basePath.'/named_ter',
        ], $loader->getPaths('named'));

        // do not use realpath here as it would make the test unuseful
        $this->assertEquals($cacheKey, str_replace('\\', '/', $loader->getCacheKey('@named/named_absolute.html')));
        $this->assertEquals("path (final)\n", $loader->getSourceContext('index.html')->getCode());
        $this->assertEquals("path (final)\n", $loader->getSourceContext('@__main__/index.html')->getCode());
        $this->assertEquals("named path (final)\n", $loader->getSourceContext('@named/index.html')->getCode());
    }

    public function getBasePaths()
    {
        return [
            [
                __DIR__.'/Fixtures',
                'tests/Loader/Fixtures/named_quater/named_absolute.html',
                null,
            ],
            [
                __DIR__.'/Fixtures/../Fixtures',
                'tests/Loader/Fixtures/named_quater/named_absolute.html',
                null,
            ],
            [
                'tests/Loader/Fixtures',
                'tests/Loader/Fixtures/named_quater/named_absolute.html',
                getcwd(),
            ],
            [
                'Fixtures',
                'Fixtures/named_quater/named_absolute.html',
                getcwd().'/tests/Loader',
            ],
            [
                'Fixtures',
                'Fixtures/named_quater/named_absolute.html',
                getcwd().'/tests/../tests/Loader',
            ],
        ];
    }

    public function testEmptyConstructor()
    {
        $loader = new FilesystemLoader();
        $this->assertEquals([], $loader->getPaths());
    }

    public function testGetNamespaces()
    {
        $loader = new FilesystemLoader(sys_get_temp_dir());
        $this->assertEquals([FilesystemLoader::MAIN_NAMESPACE], $loader->getNamespaces());

        $loader->addPath(sys_get_temp_dir(), 'named');
        $this->assertEquals([FilesystemLoader::MAIN_NAMESPACE, 'named'], $loader->getNamespaces());
    }

    public function testFindTemplateExceptionNamespace()
    {
        $basePath = __DIR__.'/Fixtures';

        $loader = new FilesystemLoader([$basePath.'/normal']);
        $loader->addPath($basePath.'/named', 'named');

        try {
            $loader->getSourceContext('@named/nowhere.html');
        } catch (\Exception $e) {
            $this->assertInstanceOf(LoaderError::class, $e);
            $this->assertStringContainsString('Unable to find template "@named/nowhere.html"', $e->getMessage());
        }
    }

    public function testFindTemplateWithCache()
    {
        $basePath = __DIR__.'/Fixtures';

        $loader = new FilesystemLoader([$basePath.'/normal']);
        $loader->addPath($basePath.'/named', 'named');

        // prime the cache for index.html in the named namespace
        $namedSource = $loader->getSourceContext('@named/index.html')->getCode();
        $this->assertEquals("named path\n", $namedSource);

        // get index.html from the main namespace
        $this->assertEquals("path\n", $loader->getSourceContext('index.html')->getCode());
    }

    public function testLoadTemplateAndRenderBlockWithCache()
    {
        $loader = new FilesystemLoader([]);
        $loader->addPath(__DIR__.'/Fixtures/themes/theme2');
        $loader->addPath(__DIR__.'/Fixtures/themes/theme1');
        $loader->addPath(__DIR__.'/Fixtures/themes/theme1', 'default_theme');

        $twig = new Environment($loader);

        $template = $twig->load('blocks.html.twig');
        $this->assertSame('block from theme 1', $template->renderBlock('b1', []));

        $template = $twig->load('blocks.html.twig');
        $this->assertSame('block from theme 2', $template->renderBlock('b2', []));
    }

    public function getArrayInheritanceTests()
    {
        return [
            'valid array inheritance' => ['array_inheritance_valid_parent.html.twig'],
            'array inheritance with empty first template' => ['array_inheritance_empty_parent.html.twig'],
            'array inheritance with non-existent first template' => ['array_inheritance_nonexistent_parent.html.twig'],
        ];
    }

    /**
     * @dataProvider getArrayInheritanceTests
     */
    public function testArrayInheritance(string $templateName)
    {
        $loader = new FilesystemLoader([]);
        $loader->addPath(__DIR__.'/Fixtures/inheritance');

        $twig = new Environment($loader);

        $template = $twig->load($templateName);
        $this->assertSame('VALID Child', $template->renderBlock('body', []));
    }

    public function testLoadTemplateFromPhar()
    {
        $loader = new FilesystemLoader([]);
        // phar-sample.phar was created with the following script:
        // $f = new Phar('phar-test.phar');
        // $f->addFromString('hello.twig', 'hello from phar');
        $loader->addPath('phar://'.__DIR__.'/Fixtures/phar/phar-sample.phar');
        $this->assertSame('hello from phar', $loader->getSourceContext('hello.twig')->getCode());
    }

    public function testTemplateExistsAlwaysReturnsBool()
    {
        $loader = new FilesystemLoader([]);
        $this->assertFalse($loader->exists("foo\0.twig"));
        $this->assertFalse($loader->exists('../foo.twig'));
        $this->assertFalse($loader->exists('@foo'));
        $this->assertFalse($loader->exists('foo'));
        $this->assertFalse($loader->exists('@foo/bar.twig'));

        $loader->addPath(__DIR__.'/Fixtures/normal');
        $this->assertTrue($loader->exists('index.html'));
        $loader->addPath(__DIR__.'/Fixtures/normal', 'foo');
        $this->assertTrue($loader->exists('@foo/index.html'));
    }
}
