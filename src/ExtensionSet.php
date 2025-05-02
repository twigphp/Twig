<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig;

use Twig\Error\RuntimeError;
use Twig\ExpressionParser\ExpressionParsers;
use Twig\Extension\AttributeExtension;
use Twig\Extension\ExtensionInterface;
use Twig\Extension\GlobalsInterface;
use Twig\Extension\LastModifiedExtensionInterface;
use Twig\Extension\StagingExtension;
use Twig\NodeVisitor\NodeVisitorInterface;
use Twig\TokenParser\TokenParserInterface;

/**
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @internal
 */
final class ExtensionSet
{
    /** @var ExtensionInterface[] */
    private array $extensions;
    private bool $initialized = false;
    private bool $runtimeInitialized = false;
    private StagingExtension $staging;

    /** @var TokenParserInterface[] */
    private array $parsers;
    /** @var NodeVisitorInterface[] */
    private array $visitors;
    /** @var array<string, TwigFilter> */
    private array $filters;
    /** @var array<string, TwigFilter> */
    private array $dynamicFilters;
    /** @var array<string, TwigTest> */
    private array $tests;
    /** @var array<string, TwigTest> */
    private array $dynamicTests;
    /** @var array<string, TwigFunction> */
    private array $functions;
    /** @var array<string, TwigFunction> */
    private array $dynamicFunctions;
    private ExpressionParsers $expressionParsers;
    /** @var array<string, mixed>|null */
    private ?array $globals = null;
    /** @var array<callable(string): (TwigFunction|false)> */
    private array $functionCallbacks = [];
    /** @var array<callable(string): (TwigFilter|false)> */
    private array $filterCallbacks = [];
    /** @var array<callable(string): (TokenParserInterface|false)> */
    private array $parserCallbacks = [];
    private int $lastModified = 0;

    public function __construct()
    {
        $this->staging = new StagingExtension();
    }

    /**
     * @return void
     */
    public function initRuntime()
    {
        $this->runtimeInitialized = true;
    }

    public function hasExtension(string $class): bool
    {
        return isset($this->extensions[ltrim($class, '\\')]);
    }

    public function getExtension(string $class): ExtensionInterface
    {
        $class = ltrim($class, '\\');

        if (!isset($this->extensions[$class])) {
            throw new RuntimeError(\sprintf('The "%s" extension is not enabled.', $class));
        }

        return $this->extensions[$class];
    }

    /**
     * @param ExtensionInterface[] $extensions
     */
    public function setExtensions(array $extensions): void
    {
        foreach ($extensions as $extension) {
            $this->addExtension($extension);
        }
    }

    /**
     * @return ExtensionInterface[]
     */
    public function getExtensions(): array
    {
        return $this->extensions;
    }

    public function getSignature(): string
    {
        return json_encode(array_keys($this->extensions));
    }

    public function isInitialized(): bool
    {
        return $this->initialized || $this->runtimeInitialized;
    }

    public function getLastModified(): int
    {
        if (0 !== $this->lastModified) {
            return $this->lastModified;
        }

        $lastModified = 0;
        foreach ($this->extensions as $extension) {
            if ($extension instanceof LastModifiedExtensionInterface) {
                $lastModified = max($extension->getLastModified(), $lastModified);
            } else {
                $r = new \ReflectionObject($extension);
                if (is_file($r->getFileName())) {
                    $lastModified = max(filemtime($r->getFileName()), $lastModified);
                }
            }
        }

        return $this->lastModified = $lastModified;
    }

    public function addExtension(ExtensionInterface $extension): void
    {
        if ($extension instanceof AttributeExtension) {
            $class = $extension->getClass();
        } else {
            $class = $extension::class;
        }

        if ($this->initialized) {
            throw new \LogicException(\sprintf('Unable to register extension "%s" as extensions have already been initialized.', $class));
        }

        if (isset($this->extensions[$class])) {
            throw new \LogicException(\sprintf('Unable to register extension "%s" as it is already registered.', $class));
        }

        $this->extensions[$class] = $extension;
    }

    public function addFunction(TwigFunction $function): void
    {
        if ($this->initialized) {
            throw new \LogicException(\sprintf('Unable to add function "%s" as extensions have already been initialized.', $function->getName()));
        }

        $this->staging->addFunction($function);
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        if (!$this->initialized) {
            $this->initExtensions();
        }

        return $this->functions;
    }

    public function getFunction(string $name): ?TwigFunction
    {
        if (!$this->initialized) {
            $this->initExtensions();
        }

        if (isset($this->functions[$name])) {
            return $this->functions[$name];
        }

        foreach ($this->dynamicFunctions as $pattern => $function) {
            if (preg_match($pattern, $name, $matches)) {
                array_shift($matches);

                return $function->withDynamicArguments($name, $function->getName(), $matches);
            }
        }

        foreach ($this->functionCallbacks as $callback) {
            if (false !== $function = $callback($name)) {
                return $function;
            }
        }

        return null;
    }

    /**
     * @param callable(string): (TwigFunction|false) $callable
     */
    public function registerUndefinedFunctionCallback(callable $callable): void
    {
        $this->functionCallbacks[] = $callable;
    }

    public function addFilter(TwigFilter $filter): void
    {
        if ($this->initialized) {
            throw new \LogicException(\sprintf('Unable to add filter "%s" as extensions have already been initialized.', $filter->getName()));
        }

        $this->staging->addFilter($filter);
    }

    /**
     * @return TwigFilter[]
     */
    public function getFilters(): array
    {
        if (!$this->initialized) {
            $this->initExtensions();
        }

        return $this->filters;
    }

    public function getFilter(string $name): ?TwigFilter
    {
        if (!$this->initialized) {
            $this->initExtensions();
        }

        if (isset($this->filters[$name])) {
            return $this->filters[$name];
        }

        foreach ($this->dynamicFilters as $pattern => $filter) {
            if (preg_match($pattern, $name, $matches)) {
                array_shift($matches);

                return $filter->withDynamicArguments($name, $filter->getName(), $matches);
            }
        }

        foreach ($this->filterCallbacks as $callback) {
            if (false !== $filter = $callback($name)) {
                return $filter;
            }
        }

        return null;
    }

    /**
     * @param callable(string): (TwigFilter|false) $callable
     */
    public function registerUndefinedFilterCallback(callable $callable): void
    {
        $this->filterCallbacks[] = $callable;
    }

    public function addNodeVisitor(NodeVisitorInterface $visitor): void
    {
        if ($this->initialized) {
            throw new \LogicException('Unable to add a node visitor as extensions have already been initialized.');
        }

        $this->staging->addNodeVisitor($visitor);
    }

    /**
     * @return NodeVisitorInterface[]
     */
    public function getNodeVisitors(): array
    {
        if (!$this->initialized) {
            $this->initExtensions();
        }

        return $this->visitors;
    }

    public function addTokenParser(TokenParserInterface $parser): void
    {
        if ($this->initialized) {
            throw new \LogicException('Unable to add a token parser as extensions have already been initialized.');
        }

        $this->staging->addTokenParser($parser);
    }

    /**
     * @return TokenParserInterface[]
     */
    public function getTokenParsers(): array
    {
        if (!$this->initialized) {
            $this->initExtensions();
        }

        return $this->parsers;
    }

    public function getTokenParser(string $name): ?TokenParserInterface
    {
        if (!$this->initialized) {
            $this->initExtensions();
        }

        if (isset($this->parsers[$name])) {
            return $this->parsers[$name];
        }

        foreach ($this->parserCallbacks as $callback) {
            if (false !== $parser = $callback($name)) {
                return $parser;
            }
        }

        return null;
    }

    /**
     * @param callable(string): (TokenParserInterface|false) $callable
     */
    public function registerUndefinedTokenParserCallback(callable $callable): void
    {
        $this->parserCallbacks[] = $callable;
    }

    /**
     * @return array<string, mixed>
     */
    public function getGlobals(): array
    {
        if (null !== $this->globals) {
            return $this->globals;
        }

        $globals = [];
        foreach ($this->extensions as $extension) {
            if (!$extension instanceof GlobalsInterface) {
                continue;
            }

            $globals = array_merge($globals, $extension->getGlobals());
        }

        if ($this->initialized) {
            $this->globals = $globals;
        }

        return $globals;
    }

    public function resetGlobals(): void
    {
        $this->globals = null;
    }

    public function addTest(TwigTest $test): void
    {
        if ($this->initialized) {
            throw new \LogicException(\sprintf('Unable to add test "%s" as extensions have already been initialized.', $test->getName()));
        }

        $this->staging->addTest($test);
    }

    /**
     * @return TwigTest[]
     */
    public function getTests(): array
    {
        if (!$this->initialized) {
            $this->initExtensions();
        }

        return $this->tests;
    }

    public function getTest(string $name): ?TwigTest
    {
        if (!$this->initialized) {
            $this->initExtensions();
        }

        if (isset($this->tests[$name])) {
            return $this->tests[$name];
        }

        foreach ($this->dynamicTests as $pattern => $test) {
            if (preg_match($pattern, $name, $matches)) {
                array_shift($matches);

                return $test->withDynamicArguments($name, $test->getName(), $matches);
            }
        }

        return null;
    }

    public function getExpressionParsers(): ExpressionParsers
    {
        if (!$this->initialized) {
            $this->initExtensions();
        }

        return $this->expressionParsers;
    }

    private function initExtensions(): void
    {
        $this->parsers = [];
        $this->filters = [];
        $this->functions = [];
        $this->tests = [];
        $this->dynamicFilters = [];
        $this->dynamicFunctions = [];
        $this->dynamicTests = [];
        $this->visitors = [];
        $this->expressionParsers = new ExpressionParsers();

        foreach ($this->extensions as $extension) {
            $this->initExtension($extension);
        }
        $this->initExtension($this->staging);
        // Done at the end only, so that an exception during initialization does not mark the environment as initialized when catching the exception
        $this->initialized = true;
    }

    private function initExtension(ExtensionInterface $extension): void
    {
        // filters
        foreach ($extension->getFilters() as $filter) {
            $this->filters[$name = $filter->getName()] = $filter;
            if (str_contains($name, '*')) {
                $this->dynamicFilters['#^'.str_replace('\\*', '(.*?)', preg_quote($name, '#')).'$#'] = $filter;
            }
        }

        // functions
        foreach ($extension->getFunctions() as $function) {
            $this->functions[$name = $function->getName()] = $function;
            if (str_contains($name, '*')) {
                $this->dynamicFunctions['#^'.str_replace('\\*', '(.*?)', preg_quote($name, '#')).'$#'] = $function;
            }
        }

        // tests
        foreach ($extension->getTests() as $test) {
            $this->tests[$name = $test->getName()] = $test;
            if (str_contains($name, '*')) {
                $this->dynamicTests['#^'.str_replace('\\*', '(.*?)', preg_quote($name, '#')).'$#'] = $test;
            }
        }

        // token parsers
        foreach ($extension->getTokenParsers() as $parser) {
            if (!$parser instanceof TokenParserInterface) {
                throw new \LogicException('getTokenParsers() must return an array of \Twig\TokenParser\TokenParserInterface.');
            }

            $this->parsers[$parser->getTag()] = $parser;
        }

        // node visitors
        foreach ($extension->getNodeVisitors() as $visitor) {
            $this->visitors[] = $visitor;
        }

        // expression parsers
        $this->expressionParsers->add($extension->getExpressionParsers());
    }
}
