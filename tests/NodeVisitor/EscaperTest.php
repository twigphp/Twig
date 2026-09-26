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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Error\Error;
use Twig\Extension\AbstractExtension;
use Twig\Loader\ArrayLoader;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Node;
use Twig\Node\Nodes;
use Twig\Node\PrintNode;
use Twig\NodeVisitor\NodeVisitorInterface;
use Twig\TwigFilter;

class EscaperTest extends TestCase
{
    /**
     * @dataProvider provideTemplates
     */
    #[DataProvider('provideTemplates')]
    public function testTemplatesFetchTheEscaperOnlyWhenTheyEscape(string|false $autoescape, string $template, int $escaperClasses, string $expected): void
    {
        $env = new Environment(new ArrayLoader(['index' => $template, 'embedded' => '{% block content %}{% endblock %}']), ['autoescape' => $autoescape]);

        $this->assertSame($escaperClasses, substr_count($env->compileSource($env->getLoader()->getSourceContext('index')), 'private \Twig\Runtime\EscaperRuntime $escaper;'));
        $this->assertSame($expected, $env->render('index', ['foo' => '<br>']));
    }

    public static function provideTemplates(): iterable
    {
        yield 'autoescaped print' => ['html', '{{ foo }}', 1, '&lt;br&gt;'];
        yield 'print marked as safe' => ['html', '{{ foo|raw }}', 0, '<br>'];
        yield 'autoescaping disabled' => [false, '{{ foo }}{# not autoescaped #}', 0, '<br>'];
        yield 'explicit escape filter' => [false, '{{ foo|e }}', 1, '&lt;br&gt;'];
        yield 'autoescape tag' => [false, '{% autoescape "html" %}{{ foo }}{% endautoescape %}', 1, '&lt;br&gt;'];
        yield 'escaping in an embedded template only' => [false, '{% embed "embedded" %}{% block content %}{{ foo|e }}{% endblock %}{% endembed %}', 1, '&lt;br&gt;'];
    }

    public function testCompilationErrorInsideAnAutoescapeTagDoesNotAffectTheNextTemplate(): void
    {
        $env = new Environment(new ArrayLoader([
            'broken.html' => '{% autoescape false %}{{ foo|failing|nl2br }}{% endautoescape %}',
            'index.html' => '{{ foo }}',
            'index.txt' => '{{ foo }}',
        ]), ['autoescape' => 'name']);
        $env->addExtension(new class extends AbstractExtension {
            public function getFilters(): array
            {
                return [new TwigFilter('failing', static fn ($value) => $value, ['is_safe_callback' => static function (): array {
                    throw new \LogicException('Unable to compute the safety of the filter.');
                }])];
            }
        });

        try {
            $env->load('broken.html');
            $this->fail('Compiling the template should fail.');
        } catch (Error) {
        }

        $this->assertSame('<br>', $env->render('index.txt', ['foo' => '<br>']));
        $this->assertSame('&lt;br&gt;', $env->render('index.html', ['foo' => '<br>']));
    }

    public function testEscapeFilterAddedByALaterVisitorStillEscapes(): void
    {
        $env = new Environment(new ArrayLoader(['index' => '{{ "<br>" }}']), ['autoescape' => false]);
        $env->addExtension(new class extends AbstractExtension {
            public function getNodeVisitors(): array
            {
                return [new class implements NodeVisitorInterface {
                    public function enterNode(Node $node, Environment $env): Node
                    {
                        return $node;
                    }

                    public function leaveNode(Node $node, Environment $env): ?Node
                    {
                        if (!$node instanceof PrintNode) {
                            return $node;
                        }

                        $filter = $env->getFilter('escape');
                        $class = $filter->getNodeClass();

                        return new PrintNode(new $class($node->getNode('expr'), $filter, new Nodes([new ConstantExpression('html', 1)]), 1), 1);
                    }

                    public function getPriority(): int
                    {
                        return 10;
                    }
                }];
            }
        });

        $this->assertSame('&lt;br&gt;', $env->render('index'));
    }
}
