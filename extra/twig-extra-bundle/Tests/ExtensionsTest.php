<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Extra\TwigExtraBundle\Tests;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Extension\ExtensionInterface;
use Twig\Extra\TwigExtraBundle\Extensions;
use Twig\Loader\ArrayLoader;

class ExtensionsTest extends TestCase
{
    /**
     * @dataProvider provideCatalog
     */
    public function testCatalogOnlyListsDeclaredCallables(array $entry): void
    {
        $extension = $this->loadExtension($entry);
        $twig = new Environment(new ArrayLoader());
        $twig->addExtension($extension);

        foreach ($entry['filters'] as $name) {
            $this->assertNotNull($twig->getFilter($name), \sprintf('The "%s" filter is listed for "%s" but the extension does not declare it, so the suggestor points to a filter that does not exist.', $name, $entry['package']));
        }

        foreach ($entry['functions'] as $name) {
            $this->assertNotNull($twig->getFunction($name), \sprintf('The "%s" function is listed for "%s" but the extension does not declare it, so the suggestor points to a function that does not exist.', $name, $entry['package']));
        }

        $tags = array_map(static fn ($parser) => $parser->getTag(), $extension->getTokenParsers());

        foreach ($entry['tags'] as $name) {
            $this->assertContains($name, $tags, \sprintf('The "%s" tag is listed for "%s" but the extension does not declare it, so the suggestor points to a tag that does not exist.', $name, $entry['package']));
        }
    }

    /**
     * @dataProvider provideCatalog
     */
    public function testCatalogListsEveryDeclaredCallable(array $entry): void
    {
        $extension = $this->loadExtension($entry);

        $this->assertCatalogCovers($entry['filters'], array_map(static fn ($filter) => $filter->getName(), $extension->getFilters()), 'filter', $entry['package']);
        $this->assertCatalogCovers($entry['functions'], array_map(static fn ($function) => $function->getName(), $extension->getFunctions()), 'function', $entry['package']);
        $this->assertCatalogCovers($entry['tags'], array_map(static fn ($parser) => $parser->getTag(), $extension->getTokenParsers()), 'tag', $entry['package']);
    }

    public static function provideCatalog(): iterable
    {
        // The catalog duplicates names owned by seven separate packages, so read it back from the constant.
        foreach ((new \ReflectionClass(Extensions::class))->getConstants()['EXTENSIONS'] as $name => $entry) {
            yield $name => [$entry];
        }
    }

    private function assertCatalogCovers(array $listed, array $declared, string $kind, string $package): void
    {
        foreach ($declared as $name) {
            if (str_contains($name, '*')) {
                // A dynamic name cannot be enumerated; the catalog spells out the variants worth suggesting.
                $this->assertNotEmpty(preg_grep('/^'.str_replace('\*', '\w+', preg_quote($name, '/')).'$/', $listed), \sprintf('The "%s" %s of "%s" is dynamic but the catalog lists none of its variants.', $name, $kind, $package));

                continue;
            }

            $this->assertContains($name, $listed, \sprintf('The "%s" %s is declared by "%s" but missing from the catalog, so nothing is suggested when a template uses it without the package installed.', $name, $kind, $package));
        }
    }

    private function loadExtension(array $entry): ExtensionInterface
    {
        if (!class_exists($entry['class'])) {
            $this->markTestSkipped(\sprintf('"%s" is not installed.', $entry['package']));
        }

        return new $entry['class']();
    }
}
