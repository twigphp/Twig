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

use Twig\Cache\CacheInterface;
use Twig\Cache\FilesystemCache;
use Twig\Cache\NullCache;
use Twig\Error\Error;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Extension\CoreExtension;
use Twig\Extension\EscaperExtension;
use Twig\Extension\ExtensionInterface;
use Twig\Extension\OptimizerExtension;
use Twig\Loader\ArrayLoader;
use Twig\Loader\ChainLoader;
use Twig\Loader\LoaderInterface;
use Twig\Node\ModuleNode;
use Twig\Node\Node;
use Twig\NodeVisitor\NodeVisitorInterface;
use Twig\RuntimeLoader\RuntimeLoaderInterface;
use Twig\TokenParser\TokenParserInterface;

/**
 * Stores the Twig configuration and renders templates.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Environment
{

    const VERSION = '3.1.2-DEV';
    const VERSION_ID = 30102;
    const MAJOR_VERSION = 3;
    const MINOR_VERSION = 1;
    const RELEASE_VERSION = 2;
    const EXTRA_VERSION = 'DEV';

    /**
     * @var
     */
    private $charset;

    /**
     * @var
     */
    private $loader;

    /**
     * @var bool
     */
    private $debug;

    /**
     * @var bool
     */
    private $autoReload;

    /**
     * @var
     */
    private $cache;

    /**
     * @var
     */
    private $lexer;

    /**
     * @var
     */
    private $parser;

    /**
     * @var
     */
    private $compiler;

    /**
     * @var array
     */
    private $globals = [];

    /**
     * @var
     */
    private $resolvedGlobals;

    /**
     * @var
     */
    private $loadedTemplates;

    /**
     * @var bool
     */
    private $strictVariables;

    /**
     * @var string
     */
    private $templateClassPrefix = '__TwigTemplate_';

    /**
     * @var
     */
    private $originalCache;

    /**
     * @var ExtensionSet
     */
    private $extensionSet;

    /**
     * @var array
     */
    private $runtimeLoaders = [];

    /**
     * @var array
     */
    private $runtimes = [];

    /**
     * @var
     */
    private $optionsHash;

    /**
     * Constructor.
     *
     * Available options:
     *
     *  * debug: When set to true, it automatically set "auto_reload" to true as
     *           well (default to false).
     *
     *  * charset: The charset used by the templates (default to UTF-8).
     *
     *  * cache: An absolute path where to store the compiled templates,
     *           a \Twig\Cache\CacheInterface implementation,
     *           or false to disable compilation cache (default).
     *
     *  * auto_reload: Whether to reload the template if the original source changed.
     *                 If you don't provide the auto_reload option, it will be
     *                 determined automatically based on the debug value.
     *
     *  * strict_variables: Whether to ignore invalid variables in templates
     *                      (default to false).
     *
     *  * autoescape: Whether to enable auto-escaping (default to html):
     *                  * false: disable auto-escaping
     *                  * html, js: set the autoescaping to one of the supported strategies
     *                  * name: set the autoescaping strategy based on the template name extension
     *                  * PHP callback: a PHP callback that returns an escaping strategy based on the template "name"
     *
     *  * optimizations: A flag that indicates which optimizations to apply
     *                   (default to -1 which means that all optimizations are enabled;
     *                   set it to 0 to disable).
     * @param LoaderInterface $loader
     * @param array $options
     */
    public function __construct(LoaderInterface $loader, $options = [])
    {
        $this->setLoader($loader);

        $options = array_merge([
            'debug' => false,
            'charset' => 'UTF-8',
            'strict_variables' => false,
            'autoescape' => 'html',
            'cache' => false,
            'auto_reload' => null,
            'optimizations' => -1,
        ], $options);

        $this->debug = (bool) $options['debug'];
        $this->setCharset($options['charset'] ?? 'UTF-8');
        $this->autoReload = null === $options['auto_reload'] ? $this->debug : (bool) $options['auto_reload'];
        $this->strictVariables = (bool) $options['strict_variables'];
        $this->setCache($options['cache']);
        $this->extensionSet = new ExtensionSet();

        $this->addExtension(new CoreExtension());
        $this->addExtension(new EscaperExtension($options['autoescape']));
        $this->addExtension(new OptimizerExtension($options['optimizations']));
    }

    /**
     * Enables debugging mode.
     */
    public function enableDebug()
    {
        $this->debug = true;
        $this->updateOptionsHash();
    }

    /**
     * Disables debugging mode.
     */
    public function disableDebug()
    {
        $this->debug = false;
        $this->updateOptionsHash();
    }

    /**
     * Checks if debug mode is enabled.
     *
     * @return bool true if debug mode is enabled, false otherwise
     */
    public function isDebug()
    {
        return $this->debug;
    }

    /**
     * Enables the auto_reload option.
     */
    public function enableAutoReload()
    {
        $this->autoReload = true;
    }

    /**
     * Disables the auto_reload option.
     */
    public function disableAutoReload()
    {
        $this->autoReload = false;
    }

    /**
     * Checks if the auto_reload option is enabled.
     *
     * @return bool true if auto_reload is enabled, false otherwise
     */
    public function isAutoReload()
    {
        return $this->autoReload;
    }

    /**
     * Enables the strict_variables option.
     */
    public function enableStrictVariables()
    {
        $this->strictVariables = true;
        $this->updateOptionsHash();
    }

    /**
     * Disables the strict_variables option.
     */
    public function disableStrictVariables()
    {
        $this->strictVariables = false;
        $this->updateOptionsHash();
    }

    /**
     * Checks if the strict_variables option is enabled.
     *
     * @return bool true if strict_variables is enabled, false otherwise
     */
    public function isStrictVariables()
    {
        return $this->strictVariables;
    }

    /**
     * Gets the current cache implementation.
     *
     * @param bool $original Whether to return the original cache option or the real cache instance
     *
     * @return CacheInterface|string|false A Twig\Cache\CacheInterface implementation,
     *                                     an absolute path to the compiled templates,
     *                                     or false to disable cache
     */
    public function getCache($original = true)
    {
        return $original ? $this->originalCache : $this->cache;
    }

    /**
     * Sets the current cache implementation.
     *
     * @param CacheInterface|string|false $cache A Twig\Cache\CacheInterface implementation,
     *                                           an absolute path to the compiled templates,
     *                                           or false to disable cache
     */
    public function setCache($cache)
    {
        if (\is_string($cache)) {
            $this->originalCache = $cache;
            $this->cache = new FilesystemCache($cache);
        } elseif (false === $cache) {
            $this->originalCache = $cache;
            $this->cache = new NullCache();
        } elseif ($cache instanceof CacheInterface) {
            $this->originalCache = $this->cache = $cache;
        } else {
            throw new \LogicException(sprintf('Cache can only be a string, false, or a \Twig\Cache\CacheInterface implementation.'));
        }
    }

    /**
     * Gets the template class associated with the given string.
     *
     * The generated template class is based on the following parameters:
     *
     *  * The cache key for the given template;
     *  * The currently enabled extensions;
     *  * Whether the Twig C extension is available or not;
     *  * PHP version;
     *  * Twig version;
     *  * Options with what environment was created.
     *
     * @param string $name The name for which to calculate the template class name
     * @param int|null $index The index if it is an embedded template
     * @return string
     *
     * @throws LoaderError
     * @internal
     */
    public function getTemplateClass(string $name, int $index = null): string
    {
        $key = $this->getLoader()->getCacheKey($name).$this->optionsHash;

        return $this->templateClassPrefix.hash('sha256', $key).(null === $index ? '' : '___'.$index);
    }

    /**
     * Renders a template.
     *
     * @param string|TemplateWrapper $name The template name
     * @param array $context
     * @return string
     *
     * @throws Error
     * @throws LoaderError When the template cannot be found
     * @throws RuntimeError When an error occurred during rendering
     * @throws SyntaxError When an error occurred during compilation
     */
    public function render($name, array $context = []): string
    {
        return $this->load($name)->render($context);
    }

    /**
     * Displays a template.
     *
     * @param string|TemplateWrapper $name The template name
     *
     * @param array $context
     * @throws Error
     * @throws LoaderError When the template cannot be found
     * @throws RuntimeError When an error occurred during rendering
     * @throws SyntaxError When an error occurred during compilation
     */
    public function display($name, array $context = []): void
    {
        $this->load($name)->display($context);
    }

    /**
     * Loads a template.
     *
     * @param string|TemplateWrapper $name The template name
     * @return TemplateWrapper
     *
     * @throws Error
     * @throws LoaderError When the template cannot be found
     * @throws RuntimeError When a previously generated cache is corrupted
     * @throws SyntaxError When an error occurred during compilation
     */
    public function load($name): TemplateWrapper
    {
        if ($name instanceof TemplateWrapper) {
            return $name;
        }

        return new TemplateWrapper($this, $this->loadTemplate($this->getTemplateClass($name), $name));
    }

    /**
     * Loads a template internal representation.
     *
     * This method is for internal use only and should never be called
     * directly.
     *
     * @param string $cls
     * @param string $name The template name
     * @param int|null $index The index if it is an embedded template
     * @return Template
     *
     * @throws Error
     * @throws LoaderError When the template cannot be found
     * @throws RuntimeError When a previously generated cache is corrupted
     * @throws SyntaxError When an error occurred during compilation
     * @internal
     */
    public function loadTemplate(string $cls, string $name, int $index = null): Template
    {
        $mainCls = $cls;
        if (null !== $index) {
            $cls .= '___'.$index;
        }

        if (isset($this->loadedTemplates[$cls])) {
            return $this->loadedTemplates[$cls];
        }

        if (!class_exists($cls, false)) {
            $key = $this->cache->generateKey($name, $mainCls);

            if (!$this->isAutoReload() || $this->isTemplateFresh($name, $this->cache->getTimestamp($key))) {
                $this->cache->load($key);
            }

            $source = null;
            if (!class_exists($cls, false)) {
                $source = $this->getLoader()->getSourceContext($name);
                $content = $this->compileSource($source);
                $this->cache->write($key, $content);
                $this->cache->load($key);

                if (!class_exists($mainCls, false)) {
                    /* Last line of defense if either $this->bcWriteCacheFile was used,
                     * $this->cache is implemented as a no-op or we have a race condition
                     * where the cache was cleared between the above calls to write to and load from
                     * the cache.
                     */
                    eval('?>'.$content);
                }

                if (!class_exists($cls, false)) {
                    throw new RuntimeError(sprintf('Failed to load Twig template "%s", index "%s": cache might be corrupted.', $name, $index), -1, $source);
                }
            }
        }

        $this->extensionSet->initRuntime();

        return $this->loadedTemplates[$cls] = new $cls($this);
    }

    /**
     * Creates a template from source.
     *
     * This method should not be used as a generic way to load templates.
     *
     * @param string $template The template source
     * @param string|null $name An optional name of the template to be used in error messages
     * @return TemplateWrapper
     *
     * @throws Error
     * @throws LoaderError When the template cannot be found
     * @throws RuntimeError
     * @throws SyntaxError When an error occurred during compilation
     */
    public function createTemplate(string $template, string $name = null): TemplateWrapper
    {
        $hash = hash('sha256', $template, false);
        if (null !== $name) {
            $name = sprintf('%s (string template %s)', $name, $hash);
        } else {
            $name = sprintf('__string_template__%s', $hash);
        }

        $loader = new ChainLoader([
            new ArrayLoader([$name => $template]),
            $current = $this->getLoader(),
        ]);

        $this->setLoader($loader);
        try {
            return new TemplateWrapper($this, $this->loadTemplate($this->getTemplateClass($name), $name));
        } finally {
            $this->setLoader($current);
        }
    }

    /**
     * Returns true if the template is still fresh.
     *
     * Besides checking the loader for freshness information,
     * this method also checks if the enabled extensions have
     * not changed.
     *
     * @param string $name
     * @param int $time The last modification time of the cached template
     * @return bool
     * @throws LoaderError
     */
    public function isTemplateFresh(string $name, int $time): bool
    {
        return $this->extensionSet->getLastModified() <= $time && $this->getLoader()->isFresh($name, $time);
    }

    /**
     * Tries to load a template consecutively from an array.
     *
     * Similar to load() but it also accepts instances of \Twig\Template and
     * \Twig\TemplateWrapper, and an array of templates where each is tried to be loaded.
     *
     * @param string|TemplateWrapper|array $names A template or an array of templates to try consecutively
     * @return TemplateWrapper
     *
     * @throws Error
     * @throws LoaderError When none of the templates can be found
     * @throws RuntimeError
     * @throws SyntaxError When an error occurred during compilation
     */
    public function resolveTemplate($names): TemplateWrapper
    {
        if (!\is_array($names)) {
            return $this->load($names);
        }

        foreach ($names as $name) {
            try {
                return $this->load($name);
            } catch (LoaderError $e) {
            }
        }

        throw new LoaderError(sprintf('Unable to find one of the following templates: "%s".', implode('", "', $names)));
    }

    /**
     * @param Lexer $lexer
     */
    public function setLexer(Lexer $lexer)
    {
        $this->lexer = $lexer;
    }

    /**
     * @param Source $source
     * @return TokenStream
     * @throws SyntaxError When the code is syntactically wrong
     */
    public function tokenize(Source $source): TokenStream
    {
        if (null === $this->lexer) {
            $this->lexer = new Lexer($this);
        }

        return $this->lexer->tokenize($source);
    }

    /**
     * @param Parser $parser
     */
    public function setParser(Parser $parser)
    {
        $this->parser = $parser;
    }

    /**
     * Converts a token stream to a node tree.
     *
     * @param TokenStream $stream
     * @return ModuleNode
     * @throws SyntaxError When the token stream is syntactically or semantically wrong
     */
    public function parse(TokenStream $stream): ModuleNode
    {
        if (null === $this->parser) {
            $this->parser = new Parser($this);
        }

        return $this->parser->parse($stream);
    }

    /**
     * @param Compiler $compiler
     */
    public function setCompiler(Compiler $compiler)
    {
        $this->compiler = $compiler;
    }

    /**
     * Compiles a node and returns the PHP code.
     * @param Node $node
     * @return string
     */
    public function compile(Node $node): string
    {
        if (null === $this->compiler) {
            $this->compiler = new Compiler($this);
        }

        return $this->compiler->compile($node)->getSource();
    }

    /**
     * Compiles a template source code.
     *
     * @param Source $source
     * @return string
     * @throws Error
     * @throws SyntaxError When there was an error during tokenizing, parsing or compiling
     */
    public function compileSource(Source $source): string
    {
        try {
            return $this->compile($this->parse($this->tokenize($source)));
        } catch (Error $e) {
            $e->setSourceContext($source);
            throw $e;
        } catch (\Exception $e) {
            throw new SyntaxError(sprintf('An exception has been thrown during the compilation of a template ("%s").', $e->getMessage()), -1, $source, $e);
        }
    }

    /**
     * @param LoaderInterface $loader
     */
    public function setLoader(LoaderInterface $loader)
    {
        $this->loader = $loader;
    }

    /**
     * @return LoaderInterface
     */
    public function getLoader(): LoaderInterface
    {
        return $this->loader;
    }

    /**
     * @param string $charset
     */
    public function setCharset(string $charset)
    {
        if ('UTF8' === $charset = strtoupper($charset)) {
            // iconv on Windows requires "UTF-8" instead of "UTF8"
            $charset = 'UTF-8';
        }

        $this->charset = $charset;
    }

    /**
     * @return string
     */
    public function getCharset(): string
    {
        return $this->charset;
    }

    /**
     * @param string $class
     * @return bool
     */
    public function hasExtension(string $class): bool
    {
        return $this->extensionSet->hasExtension($class);
    }

    /**
     * @param RuntimeLoaderInterface $loader
     */
    public function addRuntimeLoader(RuntimeLoaderInterface $loader)
    {
        $this->runtimeLoaders[] = $loader;
    }

    /**
     * @param string $class
     * @return ExtensionInterface
     * @throws RuntimeError
     */
    public function getExtension(string $class): ExtensionInterface
    {
        return $this->extensionSet->getExtension($class);
    }

    /**
     * Returns the runtime implementation of a Twig element (filter/function/test).
     *
     * @param string $class A runtime class name
     *
     * @return object The runtime implementation
     *
     * @throws RuntimeError When the template cannot be found
     */
    public function getRuntime(string $class)
    {
        if (isset($this->runtimes[$class])) {
            return $this->runtimes[$class];
        }

        foreach ($this->runtimeLoaders as $loader) {
            if (null !== $runtime = $loader->load($class)) {
                return $this->runtimes[$class] = $runtime;
            }
        }

        throw new RuntimeError(sprintf('Unable to load the "%s" runtime.', $class));
    }

    /**
     * @param ExtensionInterface $extension
     */
    public function addExtension(ExtensionInterface $extension)
    {
        $this->extensionSet->addExtension($extension);
        $this->updateOptionsHash();
    }

    /**
     * @param ExtensionInterface[] $extensions An array of extensions
     */
    public function setExtensions(array $extensions)
    {
        $this->extensionSet->setExtensions($extensions);
        $this->updateOptionsHash();
    }

    /**
     * @return ExtensionInterface[] An array of extensions (keys are for internal usage only and should not be relied on)
     */
    public function getExtensions(): array
    {
        return $this->extensionSet->getExtensions();
    }

    /**
     * @param TokenParserInterface $parser
     */
    public function addTokenParser(TokenParserInterface $parser)
    {
        $this->extensionSet->addTokenParser($parser);
    }

    /**
     * @return TokenParserInterface[]
     *
     * @internal
     */
    public function getTokenParsers(): array
    {
        return $this->extensionSet->getTokenParsers();
    }

    /**
     * @return TokenParserInterface[]
     *
     * @internal
     */
    public function getTags(): array
    {
        $tags = [];
        foreach ($this->getTokenParsers() as $parser) {
            $tags[$parser->getTag()] = $parser;
        }

        return $tags;
    }

    /**
     * @param NodeVisitorInterface $visitor
     */
    public function addNodeVisitor(NodeVisitorInterface $visitor)
    {
        $this->extensionSet->addNodeVisitor($visitor);
    }

    /**
     * @return NodeVisitorInterface[]
     *
     * @internal
     */
    public function getNodeVisitors(): array
    {
        return $this->extensionSet->getNodeVisitors();
    }

    /**
     * @param TwigFilter $filter
     */
    public function addFilter(TwigFilter $filter)
    {
        $this->extensionSet->addFilter($filter);
    }

    /**
     * @param string $name
     * @return TwigFilter|null
     * @internal
     */
    public function getFilter(string $name): ?TwigFilter
    {
        return $this->extensionSet->getFilter($name);
    }

    /**
     * @param callable $callable
     */
    public function registerUndefinedFilterCallback(callable $callable)
    {
        $this->extensionSet->registerUndefinedFilterCallback($callable);
    }

    /**
     * Gets the registered Filters.
     *
     * Be warned that this method cannot return filters defined with registerUndefinedFilterCallback.
     *
     * @return TwigFilter[]
     *
     * @see registerUndefinedFilterCallback
     *
     * @internal
     */
    public function getFilters(): array
    {
        return $this->extensionSet->getFilters();
    }

    /**
     * @param TwigTest $test
     */
    public function addTest(TwigTest $test)
    {
        $this->extensionSet->addTest($test);
    }

    /**
     * @return TwigTest[]
     *
     * @internal
     */
    public function getTests(): array
    {
        return $this->extensionSet->getTests();
    }

    /**
     * @param string $name
     * @return TwigTest|null
     * @internal
     */
    public function getTest(string $name): ?TwigTest
    {
        return $this->extensionSet->getTest($name);
    }

    /**
     * @param TwigFunction $function
     */
    public function addFunction(TwigFunction $function)
    {
        $this->extensionSet->addFunction($function);
    }

    /**
     * @param string $name
     * @return TwigFunction|null
     * @internal
     */
    public function getFunction(string $name): ?TwigFunction
    {
        return $this->extensionSet->getFunction($name);
    }

    /**
     * @param callable $callable
     */
    public function registerUndefinedFunctionCallback(callable $callable)
    {
        $this->extensionSet->registerUndefinedFunctionCallback($callable);
    }

    /**
     * Gets registered functions.
     *
     * Be warned that this method cannot return functions defined with registerUndefinedFunctionCallback.
     *
     * @return TwigFunction[]
     *
     * @see registerUndefinedFunctionCallback
     *
     * @internal
     */
    public function getFunctions(): array
    {
        return $this->extensionSet->getFunctions();
    }

    /**
     * Registers a Global.
     *
     * New globals can be added before compiling or rendering a template;
     * but after, you can only update existing globals.
     *
     * @param string $name
     * @param mixed $value The global value
     */
    public function addGlobal(string $name, $value)
    {
        if ($this->extensionSet->isInitialized() && !\array_key_exists($name, $this->getGlobals())) {
            throw new \LogicException(sprintf('Unable to add global "%s" as the runtime or the extensions have already been initialized.', $name));
        }

        if (null !== $this->resolvedGlobals) {
            $this->resolvedGlobals[$name] = $value;
        } else {
            $this->globals[$name] = $value;
        }
    }

    /**
     * @internal
     */
    public function getGlobals(): array
    {
        if ($this->extensionSet->isInitialized()) {
            if (null === $this->resolvedGlobals) {
                $this->resolvedGlobals = array_merge($this->extensionSet->getGlobals(), $this->globals);
            }

            return $this->resolvedGlobals;
        }

        return array_merge($this->extensionSet->getGlobals(), $this->globals);
    }

    /**
     * @param array $context
     * @return array
     */
    public function mergeGlobals(array $context): array
    {
        // we don't use array_merge as the context being generally
        // bigger than globals, this code is faster.
        foreach ($this->getGlobals() as $key => $value) {
            if (!\array_key_exists($key, $context)) {
                $context[$key] = $value;
            }
        }

        return $context;
    }

    /**
     * @internal
     */
    public function getUnaryOperators(): array
    {
        return $this->extensionSet->getUnaryOperators();
    }

    /**
     * @internal
     */
    public function getBinaryOperators(): array
    {
        return $this->extensionSet->getBinaryOperators();
    }

    /**
     *
     */
    private function updateOptionsHash(): void
    {
        $this->optionsHash = implode(':', [
            $this->extensionSet->getSignature(),
            PHP_MAJOR_VERSION,
            PHP_MINOR_VERSION,
            self::VERSION,
            (int) $this->debug,
            (int) $this->strictVariables,
        ]);
    }
}
