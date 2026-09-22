<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Tests\NodeVisitor;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Node\Node;
use Twig\Source;

class SafeAnalysisTest extends TestCase
{
    public function testCompilingDoesNotRetainTheAst()
    {
        $env = new Environment(new ArrayLoader(), ['cache' => false, 'autoescape' => 'html']);
        $source = new Source('{{ "a"|upper }}{{ foo.bar }}{{ [1, 2]|join("-") }}{{ b ? "y" : "n" }}', 'index');

        $ast = $env->parse($env->tokenize($source));

        $refs = [];
        $collect = static function (Node $node) use (&$collect, &$refs) {
            $refs[] = \WeakReference::create($node);
            foreach ($node as $child) {
                $collect($child);
            }
        };
        $collect($ast);
        $this->assertNotEmpty($refs);

        unset($ast, $collect);
        gc_collect_cycles();

        $retained = 0;
        foreach ($refs as $ref) {
            if (null !== $ref->get()) {
                ++$retained;
            }
        }

        $this->assertSame(0, $retained, \sprintf('%d of %d AST nodes are still referenced after compilation.', $retained, \count($refs)));
    }
}
