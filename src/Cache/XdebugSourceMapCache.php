<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Cache;

/**
 * Cache decorator that writes Xdebug source map files alongside compiled templates.
 *
 * Xdebug 3.5+ supports native path mapping via .map files in .xdebug directories.
 * This allows setting breakpoints in Twig templates and having Xdebug map them
 * to the correct lines in the compiled PHP files.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @internal
 */
final class XdebugSourceMapCache implements CacheInterface, RemovableCacheInterface
{
    private ?string $pendingTemplatePath = null;
    private ?array $pendingDebugInfo = null;

    public function __construct(
        private DirectoryCacheInterface&CacheInterface $cache,
    ) {
        $this->registerExistingSourceMaps();
    }

    /**
     * Registers all existing source map files with Xdebug.
     *
     * This is called at startup to ensure previously compiled templates
     * have their source maps available for debugging.
     */
    private function registerExistingSourceMaps(): void
    {
        foreach ($this->cache->getDirectories() as $directory) {
            $mapPath = $directory.'/.xdebug';
            if (!is_dir($mapPath)) {
                continue;
            }

            foreach (glob($mapPath.'/*.map') as $mapFile) {
                xdebug_set_source_map($mapFile);
            }
        }
    }

    public function setSourceMapData(string $templatePath, array $debugInfo): void
    {
        $this->pendingTemplatePath = $templatePath;
        $this->pendingDebugInfo = $debugInfo;
    }

    public function generateKey(string $name, string $className): string
    {
        return $this->cache->generateKey($name, $className);
    }

    public function write(string $key, string $content): void
    {
        $this->cache->write($key, $content);

        if ($this->pendingTemplatePath && $this->pendingDebugInfo) {
            try {
                $this->writeSourceMap($key, $this->pendingTemplatePath, $this->pendingDebugInfo);
            } finally {
                $this->pendingTemplatePath = null;
                $this->pendingDebugInfo = null;
            }
        }
    }

    public function load(string $key): void
    {
        $this->cache->load($key);
    }

    public function getTimestamp(string $key): int
    {
        return $this->cache->getTimestamp($key);
    }

    public function remove(string $name, string $className): void
    {
        if ($this->cache instanceof RemovableCacheInterface) {
            $this->cache->remove($name, $className);
        }

        $this->removeSourceMap($this->cache->generateKey($name, $className));
    }

    private function writeSourceMap(string $cacheKey, string $templatePath, array $debugInfo): void
    {
        $content = $this->buildMapContent($cacheKey, $templatePath, $debugInfo);
        $filename = pathinfo($cacheKey, \PATHINFO_FILENAME).'.map';

        foreach ($this->cache->getDirectories() as $directory) {
            $mapPath = $directory.'/.xdebug';

            if (!is_dir($mapPath)) {
                mkdir($mapPath, 0777, true);
            }

            $mapFile = $mapPath.'/'.$filename;
            $tmpFile = tempnam($mapPath, 'map');
            if (false !== @file_put_contents($tmpFile, $content) && @rename($tmpFile, $mapFile)) {
                @chmod($mapFile, 0666 & ~umask());
                xdebug_set_source_map($mapFile);

                continue;
            }

            throw new \RuntimeException(\sprintf('Failed to write Xdebug source map file "%s".', $mapFile));
        }
    }

    private function removeSourceMap(string $cacheKey): void
    {
        $filename = pathinfo($cacheKey, \PATHINFO_FILENAME).'.map';

        foreach ($this->cache->getDirectories() as $directory) {
            $mapFile = $directory.'/.xdebug/'.$filename;
            if (is_file($mapFile)) {
                @unlink($mapFile);
            }
        }
    }

    private function buildMapContent(string $cacheKey, string $templatePath, array $debugInfo): string
    {
        $lines = "# Xdebug source map for Twig template\n";
        $lines .= \sprintf("remote_prefix: %s/\n", \dirname($cacheKey));
        $lines .= \sprintf("local_prefix: %s/\n\n", \dirname($templatePath));

        $compiledLines = array_keys($debugInfo);
        for ($i = 0, $count = \count($compiledLines); $i < $count; ++$i) {
            $startLine = $compiledLines[$i];
            // End line is exclusive, so use next start line minus 1
            // Use a reasonable max for last range (Xdebug can't handle PHP_INT_MAX)
            $endLine = isset($compiledLines[$i + 1]) ? $compiledLines[$i + 1] - 1 : 999999;
            $lines .= \sprintf(
                "%s:%d-%d = %s:%d\n",
                basename($cacheKey),
                $startLine,
                $endLine,
                basename($templatePath),
                $debugInfo[$startLine],
            );
        }

        return $lines;
    }
}
