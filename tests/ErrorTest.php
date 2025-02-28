<?php

namespace Twig\Tests;

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use PHPUnit\Framework\TestCase;
use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Environment;
use Twig\Error\Error;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Loader\ArrayLoader;
use Twig\Loader\FilesystemLoader;
use Twig\Loader\LoaderInterface;
use Twig\Node\Node;
use Twig\Source;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

class ErrorTest extends TestCase
{
    public function testErrorWithObjectFilename()
    {
        $error = new Error('foo');
        $error->setSourceContext(new Source('', new \SplFileInfo(__FILE__)));

        $this->assertStringContainsString('tests'.\DIRECTORY_SEPARATOR.'ErrorTest.php', $error->getMessage());
    }

    public function testTwigExceptionGuessWithMissingVarAndArrayLoader()
    {
        $loader = new ArrayLoader([
            'base.html' => '{% block content %}{% endblock %}',
            'index.html' => <<<EOHTML
{% extends 'base.html' %}
{% block content %}
    {{ foo.bar }}
{% endblock %}
{% block foo %}
    {{ foo.bar }}
{% endblock %}
EOHTML,
        ]);

        $twig = new Environment($loader, ['strict_variables' => true, 'debug' => true, 'cache' => false]);

        $template = $twig->load('index.html');
        try {
            $template->render([]);

            $this->fail();
        } catch (RuntimeError $e) {
            $this->assertEquals('Variable "foo" does not exist in "index.html" at line 3.', $e->getMessage());
            $this->assertEquals(3, $e->getTemplateLine());
            $this->assertEquals('index.html', $e->getSourceContext()->getName());
        }
    }

    public function testTwigExceptionGuessWithExceptionAndArrayLoader()
    {
        $loader = new ArrayLoader([
            'base.html' => '{% block content %}{% endblock %}',
            'index.html' => <<<EOHTML
{% extends 'base.html' %}
{% block content %}
    {{ foo.bar }}
{% endblock %}
{% block foo %}
    {{ foo.bar }}
{% endblock %}
EOHTML,
        ]);
        $twig = new Environment($loader, ['strict_variables' => true, 'debug' => true, 'cache' => false]);

        $template = $twig->load('index.html');
        try {
            $template->render(['foo' => new ErrorTest_Foo()]);

            $this->fail();
        } catch (RuntimeError $e) {
            $this->assertEquals('An exception has been thrown during the rendering of a template ("Runtime error...") in "index.html" at line 3.', $e->getMessage());
            $this->assertEquals(3, $e->getTemplateLine());
            $this->assertEquals('index.html', $e->getSourceContext()->getName());
        }
    }

    public function testTwigExceptionGuessWithMissingVarAndFilesystemLoader()
    {
        $loader = new FilesystemLoader(__DIR__.'/Fixtures/errors');
        $twig = new Environment($loader, ['strict_variables' => true, 'debug' => true, 'cache' => false]);

        $template = $twig->load('index.html');
        try {
            $template->render([]);

            $this->fail();
        } catch (RuntimeError $e) {
            $this->assertEquals('Variable "foo" does not exist in "index.html" at line 3.', $e->getMessage());
            $this->assertEquals(3, $e->getTemplateLine());
            $this->assertEquals('index.html', $e->getSourceContext()->getName());
            $this->assertEquals(3, $e->getLine());
            $this->assertEquals(strtr(__DIR__.'/Fixtures/errors/index.html', '/', \DIRECTORY_SEPARATOR), $e->getFile());
        }
    }

    public function testTwigExceptionGuessWithExceptionAndFilesystemLoader()
    {
        $loader = new FilesystemLoader(__DIR__.'/Fixtures/errors');
        $twig = new Environment($loader, ['strict_variables' => true, 'debug' => true, 'cache' => false]);

        $template = $twig->load('index.html');
        try {
            $template->render(['foo' => new ErrorTest_Foo()]);

            $this->fail();
        } catch (RuntimeError $e) {
            $this->assertEquals('An exception has been thrown during the rendering of a template ("Runtime error...") in "index.html" at line 3.', $e->getMessage());
            $this->assertEquals(3, $e->getTemplateLine());
            $this->assertEquals('index.html', $e->getSourceContext()->getName());
            $this->assertEquals(3, $e->getLine());
            $this->assertEquals(strtr(__DIR__.'/Fixtures/errors/index.html', '/', \DIRECTORY_SEPARATOR), $e->getFile());
        }
    }

    /**
     * @dataProvider getErroredTemplates
     */
    public function testTwigExceptionAddsFileAndLine($templates, $name, $line)
    {
        $loader = new ArrayLoader($templates);
        $twig = new Environment($loader, ['strict_variables' => true, 'debug' => true, 'cache' => false]);

        $template = $twig->load('index');

        try {
            $template->render([]);

            $this->fail();
        } catch (RuntimeError $e) {
            $this->assertEquals(\sprintf('Variable "foo" does not exist in "%s" at line %d.', $name, $line), $e->getMessage());
            $this->assertEquals($line, $e->getTemplateLine());
            $this->assertEquals($name, $e->getSourceContext()->getName());
        }

        try {
            $template->render(['foo' => new ErrorTest_Foo()]);

            $this->fail();
        } catch (RuntimeError $e) {
            $this->assertEquals(\sprintf('An exception has been thrown during the rendering of a template ("Runtime error...") in "%s" at line %d.', $name, $line), $e->getMessage());
            $this->assertEquals($line, $e->getTemplateLine());
            $this->assertEquals($name, $e->getSourceContext()->getName());
        }
    }

    public function testTwigArrayFilterThrowsRuntimeExceptions()
    {
        $loader = new ArrayLoader([
            'filter-null.html' => <<<EOHTML
{# Argument 1 passed to IteratorIterator::__construct() must implement interface Traversable, null given: #}
{% for n in variable|filter(x => x > 3) %}
    This list contains {{n}}.
{% endfor %}
EOHTML,
        ]);

        $twig = new Environment($loader, ['debug' => true, 'cache' => false]);

        $template = $twig->load('filter-null.html');
        $out = $template->render(['variable' => [1, 2, 3, 4]]);
        $this->assertEquals('This list contains 4.', trim($out));

        try {
            $template->render(['variable' => null]);

            $this->fail();
        } catch (RuntimeError $e) {
            $this->assertEquals(2, $e->getTemplateLine());
            $this->assertEquals('filter-null.html', $e->getSourceContext()->getName());
        }
    }

    public function testTwigArrayMapThrowsRuntimeExceptions()
    {
        $loader = new ArrayLoader([
            'map-null.html' => <<<EOHTML
{# We expect a runtime error if `variable` is not traversable #}
{% for n in variable|map(x => x * 3) %}
    {{- n -}}
{% endfor %}
EOHTML,
        ]);

        $twig = new Environment($loader, ['debug' => true, 'cache' => false]);

        $template = $twig->load('map-null.html');
        $out = $template->render(['variable' => [1, 2, 3, 4]]);
        $this->assertEquals('36912', trim($out));

        try {
            $template->render(['variable' => null]);

            $this->fail();
        } catch (RuntimeError $e) {
            $this->assertEquals(2, $e->getTemplateLine());
            $this->assertEquals('map-null.html', $e->getSourceContext()->getName());
        }
    }

    public function testTwigArrayReduceThrowsRuntimeExceptions()
    {
        $loader = new ArrayLoader([
            'reduce-null.html' => <<<EOHTML
{# We expect a runtime error if `variable` is not traversable #}
{{ variable|reduce((carry, x) => carry + x) }}
EOHTML,
        ]);

        $twig = new Environment($loader, ['debug' => true, 'cache' => false]);

        $template = $twig->load('reduce-null.html');
        $out = $template->render(['variable' => [1, 2, 3, 4]]);
        $this->assertEquals('10', trim($out));

        try {
            $template->render(['variable' => null]);

            $this->fail();
        } catch (RuntimeError $e) {
            $this->assertEquals(2, $e->getTemplateLine());
            $this->assertEquals('reduce-null.html', $e->getSourceContext()->getName());
        }
    }

    public function testTwigExceptionUpdateFileAndLineTogether()
    {
        $twig = new Environment(new ArrayLoader([
            'index' => "\n\n\n\n{{ foo() }}",
        ]), ['debug' => true, 'cache' => false]);

        try {
            $twig->load('index')->render([]);
        } catch (SyntaxError $e) {
            $this->assertSame('Unknown "foo" function in "index" at line 5.', $e->getMessage());
            $this->assertSame(5, $e->getTemplateLine());
            // as we are using an ArrayLoader, we don't have a file, so the line should not be the template line,
            // but the line of the error in the Parser.php file
            $this->assertStringContainsString('Parser.php', $e->getFile());
            $this->assertNotSame(5, $e->getLine());
        }
    }

    /**
     * @dataProvider getErrorWithoutLineAndContextData
     */
    public function testErrorWithoutLineAndContext(LoaderInterface $loader, bool $debug, bool $addDebugInfo, bool $exceptionWithLineAndContext, int $errorLine)
    {
        $twig = new Environment($loader, ['debug' => $debug, 'cache' => false]);
        $twig->removeCache('no_line_and_context_exception.twig');
        $twig->removeCache('no_line_and_context_exception_include_line_5.twig');
        $twig->removeCache('no_line_and_context_exception_include_line_1.twig');
        $twig->addTokenParser(new class($addDebugInfo, $exceptionWithLineAndContext) extends AbstractTokenParser {
            public function __construct(private bool $addDebugInfo, private bool $exceptionWithLineAndContext)
            {
            }

            public function parse(Token $token)
            {
                $stream = $this->parser->getStream();
                $lineno = $stream->getCurrent()->getLine();
                $stream->expect(Token::BLOCK_END_TYPE);

                return new #[YieldReady]class($lineno, $this->addDebugInfo, $this->exceptionWithLineAndContext) extends Node
                {
                    public function __construct(int $lineno, private bool $addDebugInfo, private bool $exceptionWithLineAndContext)
                    {
                        parent::__construct([], [], $lineno);
                    }

                    public function compile(Compiler $compiler): void
                    {
                        if ($this->addDebugInfo) {
                            $compiler->addDebugInfo($this);
                        }
                        if ($this->exceptionWithLineAndContext) {
                            $compiler
                                ->write('throw new \Twig\Error\RuntimeError("Runtime error.", ')
                                ->repr($this->lineno)->raw(", \$this->getSourceContext()")
                                ->raw(");\n")
                            ;
                        } else {
                            $compiler->write('throw new \Twig\Error\RuntimeError("Runtime error.");');
                        }
                    }
                };
            }

            public function getTag()
            {
                return 'foo';
            }
        });

        try {
            $twig->render('no_line_and_context_exception.twig', ['line' => $errorLine]);
            $this->fail();
        } catch (RuntimeError $e) {
            if (1 === $errorLine && !$addDebugInfo && !$exceptionWithLineAndContext) {
                // When the template only has the custom node that throws the error, we cannot find the line of the error
                // as we have no debug info and no line and context in the exception
                $this->assertSame(\sprintf('Runtime error in "no_line_and_context_exception_include_line_%d.twig".', $errorLine), $e->getMessage());
                $this->assertSame(0, $e->getTemplateLine());
            } else {
                // When the template has some space before the custom node, the associated TextNode outputs some debug info at line 1
                // that's why the line is 1 when we have no debug info and no line and context in the exception
                $line = $addDebugInfo || $exceptionWithLineAndContext ? $errorLine : 1;
                $this->assertSame(\sprintf('Runtime error in "no_line_and_context_exception_include_line_%d.twig" at line %d.', $errorLine, $line), $e->getMessage());
                $this->assertSame($line, $e->getTemplateLine());
            }

            $line = $addDebugInfo || $exceptionWithLineAndContext ? $errorLine : 1;
            if ($loader instanceof FilesystemLoader) {
                $this->assertStringContainsString(\sprintf('errors/no_line_and_context_exception_include_line_%d.twig', $errorLine), $e->getFile());
                $line = $addDebugInfo || $exceptionWithLineAndContext ? $errorLine : (1 === $errorLine ? -1 : 1);
                $this->assertSame($line, $e->getLine());
            } else {
                $this->assertStringContainsString('Environment.php', $e->getFile());
                $this->assertNotSame($line, $e->getLine());
            }
        }
    }

    public static function getErrorWithoutLineAndContextData(): iterable
    {
        $fileLoaders = [
            new ArrayLoader([
                'no_line_and_context_exception.twig' => "\n\n{{ include('no_line_and_context_exception_include_line_' ~ line ~ '.twig') }}",
                'no_line_and_context_exception_include_line_5.twig' => "\n\n\n\n{% foo %}",
                'no_line_and_context_exception_include_line_1.twig' => '{% foo %}',
            ]),
            new FilesystemLoader(__DIR__.'/Fixtures/errors'),
        ];

        foreach ($fileLoaders as $loader) {
            foreach ([false, true] as $exceptionWithLineAndContext) {
                foreach ([false, true] as $addDebugInfo) {
                    foreach ([false, true] as $debug) {
                        foreach ([5, 1] as $line) {
                            $name = ($loader instanceof FilesystemLoader ? 'filesystem' : 'array')
                                .($debug ? '_with_debug' : '_without_debug')
                                .($addDebugInfo ? '_with_debug_info' : '_without_debug_info')
                                .($exceptionWithLineAndContext ? '_with_context' : '_without_context')
                                .('_line_'.$line)
                            ;
                            yield $name => [$loader, $debug, $addDebugInfo, $exceptionWithLineAndContext, $line];
                        }
                    }
                }
            }
        }
    }

    public static function getErroredTemplates()
    {
        return [
            // error occurs in a template
            [
                [
                    'index' => "\n\n{{ foo.bar }}\n\n\n{{ 'foo' }}",
                ],
                'index', 3,
            ],

            // error occurs in an included template
            [
                [
                    'index' => "{% include 'partial' %}",
                    'partial' => '{{ foo.bar }}',
                ],
                'partial', 1,
            ],

            // error occurs in a parent block when called via parent()
            [
                [
                    'index' => "{% extends 'base' %}
                    {% block content %}
                        {{ parent() }}
                    {% endblock %}",
                    'base' => '{% block content %}{{ foo.bar }}{% endblock %}',
                ],
                'base', 1,
            ],

            // error occurs in a block from the child
            [
                [
                    'index' => "{% extends 'base' %}
                    {% block content %}
                        {{ foo.bar }}
                    {% endblock %}
                    {% block foo %}
                        {{ foo.bar }}
                    {% endblock %}",
                    'base' => '{% block content %}{% endblock %}',
                ],
                'index', 3,
            ],

            // error occurs in an embed tag
            [
                [
                    'index' => "
                    {% embed 'base' %}
                    {% endembed %}",
                    'base' => '{% block foo %}{{ foo.bar }}{% endblock %}',
                ],
                'base', 1,
            ],

            // error occurs in an overridden block from an embed tag
            [
                [
                    'index' => "
                    {% embed 'base' %}
                        {% block foo %}
                            {{ foo.bar }}
                        {% endblock %}
                    {% endembed %}",
                    'base' => '{% block foo %}{% endblock %}',
                ],
                'index', 4,
            ],
        ];
    }

    public function testErrorFromArrayLoader()
    {
        $templates = [
            'index.twig' => '{% include "include.twig" %}',
            'include.twig' => $include = <<<EOF



            {% extends 'invalid.twig' %}
            EOF,
        ];
        $twig = new Environment(new ArrayLoader($templates), ['debug' => true, 'cache' => false]);
        try {
            $twig->render('index.twig');
            $this->fail('Expected LoaderError to be thrown');
        } catch (LoaderError $e) {
            $this->assertSame('Template "invalid.twig" is not defined.', $e->getRawMessage());
            $this->assertSame(4, $e->getTemplateLine());
            $this->assertSame('include.twig', $e->getSourceContext()->getName());
            $this->assertSame($include, $e->getSourceContext()->getCode());
        }
    }

    public function testErrorFromFilesystemLoader()
    {
        $twig = new Environment(new FilesystemLoader([$dir = __DIR__.'/Fixtures/errors/extends']), ['debug' => true, 'cache' => false]);
        $include = file_get_contents($dir.'/include.twig');
        try {
            $twig->render('index.twig');
            $this->fail('Expected LoaderError to be thrown');
        } catch (LoaderError $e) {
            $this->assertStringContainsString('Unable to find template "invalid.twig"', $e->getRawMessage());
            $this->assertSame(4, $e->getTemplateLine());
            $this->assertSame('include.twig', $e->getSourceContext()->getName());
            $this->assertSame($include, $e->getSourceContext()->getCode());
        }
    }
}

class ErrorTest_Foo
{
    public function bar()
    {
        throw new \Exception('Runtime error...');
    }
}
