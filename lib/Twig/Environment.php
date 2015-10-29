<?php

/*
 * This file is part of Twig.
 *
 * (c) 2009 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Stores the Twig configuration.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Twig_Environment
{
    const VERSION = '2.0.0-DEV';

    private $charset;
    private $loader;
    private $debug;
    private $autoReload;
    private $cache;
    private $lexer;
    private $parser;
    private $compiler;
    private $baseTemplateClass;
    private $extensions;
    private $parsers;
    private $visitors;
    private $filters;
    private $tests;
    private $functions;
    private $globals;
    private $runtimeInitialized = false;
    private $extensionInitialized = false;
    private $loadedTemplates;
    private $strictVariables;
    private $unaryOperators;
    private $binaryOperators;
    private $templateClassPrefix = '__TwigTemplate_';
    private $functionCallbacks = array();
    private $filterCallbacks = array();
    private $staging;
    private $originalCache;
    private $lastModifiedExtension = 0;

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
     *  * base_template_class: The base template class to use for generated
     *                         templates (default to Twig_Template).
     *
     *  * cache: An absolute path where to store the compiled templates,
     *           a Twig_Cache_Interface implementation,
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
     *                  * filename: set the autoescaping strategy based on the template filename extension
     *                  * PHP callback: a PHP callback that returns an escaping strategy based on the template "filename"
     *
     *  * optimizations: A flag that indicates which optimizations to apply
     *                   (default to -1 which means that all optimizations are enabled;
     *                   set it to 0 to disable).
     *
     * @param Twig_LoaderInterface $loader  A Twig_LoaderInterface instance
     * @param array                $options An array of options
     */
    public function __construct(Twig_LoaderInterface $loader, $options = array())
    {
        $this->setLoader($loader);

        $options = array_merge(array(
            'debug' => false,
            'charset' => 'UTF-8',
            'base_template_class' => 'Twig_Template',
            'strict_variables' => false,
            'autoescape' => 'html',
            'cache' => false,
            'auto_reload' => null,
            'optimizations' => -1,
        ), $options);

        $this->debug = (bool) $options['debug'];
        $this->setCharset($options['charset']);
        $this->baseTemplateClass = $options['base_template_class'];
        $this->autoReload = null === $options['auto_reload'] ? $this->debug : (bool) $options['auto_reload'];
        $this->strictVariables = (bool) $options['strict_variables'];
        $this->setCache($options['cache']);

        $this->addExtension(new Twig_Extension_Core());
        $this->addExtension(new Twig_Extension_Escaper($options['autoescape']));
        $this->addExtension(new Twig_Extension_Optimizer($options['optimizations']));
        $this->staging = new Twig_Extension_Staging();
    }

    /**
     * Gets the base template class for compiled templates.
     *
     * @return string The base template class name
     */
    public function getBaseTemplateClass()
    {
        return $this->baseTemplateClass;
    }

    /**
     * Sets the base template class for compiled templates.
     *
     * @param string $class The base template class name
     */
    public function setBaseTemplateClass($class)
    {
        $this->baseTemplateClass = $class;
    }

    /**
     * Enables debugging mode.
     */
    public function enableDebug()
    {
        $this->debug = true;
    }

    /**
     * Disables debugging mode.
     */
    public function disableDebug()
    {
        $this->debug = false;
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
    }

    /**
     * Disables the strict_variables option.
     */
    public function disableStrictVariables()
    {
        $this->strictVariables = false;
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
     * @return Twig_CacheInterface|string|false A Twig_CacheInterface implementation,
     *                                          an absolute path to the compiled templates,
     *                                          or false to disable cache
     */
    public function getCache($original = true)
    {
        return $original ? $this->originalCache : $this->cache;
    }

    /**
     * Sets the current cache implementation.
     *
     * @param Twig_CacheInterface|string|false $cache A Twig_CacheInterface implementation,
     *                                                an absolute path to the compiled templates,
     *                                                or false to disable cache
     */
    public function setCache($cache)
    {
        if (is_string($cache)) {
            $this->originalCache = $cache;
            $this->cache = new Twig_Cache_Filesystem($cache);
        } elseif (false === $cache) {
            $this->originalCache = $cache;
            $this->cache = new Twig_Cache_Null();
        } elseif ($cache instanceof Twig_CacheInterface) {
            $this->originalCache = $this->cache = $cache;
        } else {
            throw new LogicException(sprintf('Cache can only be a string, false, or a Twig_CacheInterface implementation.'));
        }
    }

    /**
     * Gets the template class associated with the given string.
     *
     * The generated template class is based on the following parameters:
     *
     *  * The cache key for the given template;
     *  * The currently enabled extensions;
     *  * Whether the Twig C extension is available or not.
     *
     * @param string   $name  The name for which to calculate the template class name
     * @param int|null $index The index if it is an embedded template
     *
     * @return string The template class name
     */
    public function getTemplateClass($name, $index = null)
    {
        $key = $this->getLoader()->getCacheKey($name);
        $key .= json_encode(array_keys($this->extensions));
        $key .= function_exists('twig_template_get_attributes');

        return $this->templateClassPrefix.hash('sha256', $key).(null === $index ? '' : '_'.$index);
    }

    /**
     * Renders a template.
     *
     * @param string $name    The template name
     * @param array  $context An array of parameters to pass to the template
     *
     * @return string The rendered template
     *
     * @throws Twig_Error_Loader  When the template cannot be found
     * @throws Twig_Error_Syntax  When an error occurred during compilation
     * @throws Twig_Error_Runtime When an error occurred during rendering
     */
    public function render($name, array $context = array())
    {
        return $this->loadTemplate($name)->render($context);
    }

    /**
     * Displays a template.
     *
     * @param string $name    The template name
     * @param array  $context An array of parameters to pass to the template
     *
     * @throws Twig_Error_Loader  When the template cannot be found
     * @throws Twig_Error_Syntax  When an error occurred during compilation
     * @throws Twig_Error_Runtime When an error occurred during rendering
     */
    public function display($name, array $context = array())
    {
        $this->loadTemplate($name)->display($context);
    }

    /**
     * Loads a template by name.
     *
     * @param string $name  The template name
     * @param int    $index The index if it is an embedded template
     *
     * @return Twig_Template A template instance representing the given template name
     *
     * @throws Twig_Error_Loader When the template cannot be found
     * @throws Twig_Error_Syntax When an error occurred during compilation
     */
    public function loadTemplate($name, $index = null)
    {
        $cls = $this->getTemplateClass($name, $index);

        if (isset($this->loadedTemplates[$cls])) {
            return $this->loadedTemplates[$cls];
        }

        if (!class_exists($cls, false)) {
            $key = $this->cache->generateKey($name, $cls);

            if (!$this->isAutoReload() || $this->isTemplateFresh($name, $this->cache->getTimestamp($key))) {
                $this->cache->load($key);
            }

            if (!class_exists($cls, false)) {
                $content = $this->compileSource($this->getLoader()->getSource($name), $name);
                $this->cache->write($key, $content);

                eval('?>'.$content);
            }
        }

        if (!$this->runtimeInitialized) {
            $this->initRuntime();
        }

        return $this->loadedTemplates[$cls] = new $cls($this);
    }

    /**
     * Creates a template from source.
     *
     * This method should not be used as a generic way to load templates.
     *
     * @param string $template The template name
     *
     * @return Twig_Template A template instance representing the given template name
     *
     * @throws Twig_Error_Loader When the template cannot be found
     * @throws Twig_Error_Syntax When an error occurred during compilation
     */
    public function createTemplate($template)
    {
        $name = sprintf('__string_template__%s', hash('sha256', uniqid(mt_rand(), true), false));

        $loader = new Twig_Loader_Chain(array(
            new Twig_Loader_Array(array($name => $template)),
            $current = $this->getLoader(),
        ));

        $this->setLoader($loader);
        try {
            $template = $this->loadTemplate($name);
        } finally {
            $this->setLoader($current);
        }

        return $template;
    }

    /**
     * Returns true if the template is still fresh.
     *
     * Besides checking the loader for freshness information,
     * this method also checks if the enabled extensions have
     * not changed.
     *
     * @param string $name The template name
     * @param int    $time The last modification time of the cached template
     *
     * @return bool true if the template is fresh, false otherwise
     */
    public function isTemplateFresh($name, $time)
    {
        if (0 === $this->lastModifiedExtension) {
            foreach ($this->extensions as $extension) {
                $r = new ReflectionObject($extension);
                if (file_exists($r->getFileName()) && ($extensionTime = filemtime($r->getFileName())) > $this->lastModifiedExtension) {
                    $this->lastModifiedExtension = $extensionTime;
                }
            }
        }

        return $this->lastModifiedExtension <= $time && $this->getLoader()->isFresh($name, $time);
    }

    /**
     * Tries to load a template consecutively from an array.
     *
     * Similar to loadTemplate() but it also accepts Twig_Template instances and an array
     * of templates where each is tried to be loaded.
     *
     * @param string|Twig_Template|array $names A template or an array of templates to try consecutively
     *
     * @return Twig_Template
     *
     * @throws Twig_Error_Loader When none of the templates can be found
     * @throws Twig_Error_Syntax When an error occurred during compilation
     */
    public function resolveTemplate($names)
    {
        if (!is_array($names)) {
            $names = array($names);
        }

        foreach ($names as $name) {
            if ($name instanceof Twig_Template) {
                return $name;
            }

            try {
                return $this->loadTemplate($name);
            } catch (Twig_Error_Loader $e) {
            }
        }

        if (1 === count($names)) {
            throw $e;
        }

        throw new Twig_Error_Loader(sprintf('Unable to find one of the following templates: "%s".', implode('", "', $names)));
    }

    /**
     * Gets the Lexer instance.
     *
     * @return Twig_Lexer A Twig_Lexer instance
     */
    public function getLexer()
    {
        if (null === $this->lexer) {
            $this->lexer = new Twig_Lexer($this);
        }

        return $this->lexer;
    }

    /**
     * Sets the Lexer instance.
     *
     * @param Twig_Lexer $lexer A Twig_Lexer instance
     */
    public function setLexer(Twig_Lexer $lexer)
    {
        $this->lexer = $lexer;
    }

    /**
     * Tokenizes a source code.
     *
     * @param string $source The template source code
     * @param string $name   The template name
     *
     * @return Twig_TokenStream A Twig_TokenStream instance
     *
     * @throws Twig_Error_Syntax When the code is syntactically wrong
     */
    public function tokenize($source, $name = null)
    {
        return $this->getLexer()->tokenize($source, $name);
    }

    /**
     * Gets the Parser instance.
     *
     * @return Twig_Parser A Twig_Parser instance
     */
    public function getParser()
    {
        if (null === $this->parser) {
            $this->parser = new Twig_Parser($this);
        }

        return $this->parser;
    }

    /**
     * Sets the Parser instance.
     *
     * @param Twig_Parser $parser A Twig_Parser instance
     */
    public function setParser(Twig_Parser $parser)
    {
        $this->parser = $parser;
    }

    /**
     * Converts a token stream to a node tree.
     *
     * @param Twig_TokenStream $stream A token stream instance
     *
     * @return Twig_Node_Module A node tree
     *
     * @throws Twig_Error_Syntax When the token stream is syntactically or semantically wrong
     */
    public function parse(Twig_TokenStream $stream)
    {
        return $this->getParser()->parse($stream);
    }

    /**
     * Gets the Compiler instance.
     *
     * @return Twig_Compiler A Twig_Compiler instance
     */
    public function getCompiler()
    {
        if (null === $this->compiler) {
            $this->compiler = new Twig_Compiler($this);
        }

        return $this->compiler;
    }

    /**
     * Sets the Compiler instance.
     *
     * @param Twig_Compiler $compiler A Twig_Compiler instance
     */
    public function setCompiler(Twig_Compiler $compiler)
    {
        $this->compiler = $compiler;
    }

    /**
     * Compiles a node and returns the PHP code.
     *
     * @param Twig_Node $node A Twig_Node instance
     *
     * @return string The compiled PHP source code
     */
    public function compile(Twig_Node $node)
    {
        return $this->getCompiler()->compile($node)->getSource();
    }

    /**
     * Compiles a template source code.
     *
     * @param string $source The template source code
     * @param string $name   The template name
     *
     * @return string The compiled PHP source code
     *
     * @throws Twig_Error_Syntax When there was an error during tokenizing, parsing or compiling
     */
    public function compileSource($source, $name = null)
    {
        try {
            $compiled = $this->compile($this->parse($this->tokenize($source, $name)), $source);

            if (isset($source[0])) {
                $compiled .= '/* '.str_replace(array('*/', "\r\n", "\r", "\n"), array('*//* ', "\n", "\n", "*/\n/* "), $source)."*/\n";
            }

            return $compiled;
        } catch (Twig_Error $e) {
            $e->setTemplateFile($name);
            throw $e;
        } catch (Exception $e) {
            throw new Twig_Error_Syntax(sprintf('An exception has been thrown during the compilation of a template ("%s").', $e->getMessage()), -1, $name, $e);
        }
    }

    /**
     * Sets the Loader instance.
     *
     * @param Twig_LoaderInterface $loader A Twig_LoaderInterface instance
     */
    public function setLoader(Twig_LoaderInterface $loader)
    {
        $this->loader = $loader;
    }

    /**
     * Gets the Loader instance.
     *
     * @return Twig_LoaderInterface A Twig_LoaderInterface instance
     */
    public function getLoader()
    {
        return $this->loader;
    }

    /**
     * Sets the default template charset.
     *
     * @param string $charset The default charset
     */
    public function setCharset($charset)
    {
        if ('UTF8' === $charset = strtoupper($charset)) {
            // iconv on Windows requires "UTF-8" instead of "UTF8"
            $charset = 'UTF-8';
        }

        $this->charset = $charset;
    }

    /**
     * Gets the default template charset.
     *
     * @return string The default charset
     */
    public function getCharset()
    {
        return $this->charset;
    }

    /**
     * Initializes the runtime environment.
     */
    public function initRuntime()
    {
        $this->runtimeInitialized = true;

        foreach ($this->getExtensions() as $extension) {
            if ($extension instanceof Twig_Extension_InitRuntimeInterface) {
                $extension->initRuntime($this);
            }
        }
    }

    /**
     * Returns true if the given extension is registered.
     *
     * @param string $name The extension name
     *
     * @return bool Whether the extension is registered or not
     */
    public function hasExtension($name)
    {
        return isset($this->extensions[$name]);
    }

    /**
     * Gets an extension by name.
     *
     * @param string $name The extension name
     *
     * @return Twig_ExtensionInterface A Twig_ExtensionInterface instance
     */
    public function getExtension($name)
    {
        if (!isset($this->extensions[$name])) {
            throw new Twig_Error_Runtime(sprintf('The "%s" extension is not enabled.', $name));
        }

        return $this->extensions[$name];
    }

    /**
     * Registers an extension.
     *
     * @param Twig_ExtensionInterface $extension A Twig_ExtensionInterface instance
     */
    public function addExtension(Twig_ExtensionInterface $extension)
    {
        $name = $extension->getName();

        if ($this->extensionInitialized) {
            throw new LogicException(sprintf('Unable to register extension "%s" as extensions have already been initialized.', $name));
        }

        if (isset($this->extensions[$name])) {
            throw new LogicException(sprintf('Unable to register extension "%s" as it is already registered.', $name));
        }

        $this->lastModifiedExtension = 0;

        $this->extensions[$name] = $extension;
    }

    /**
     * Registers an array of extensions.
     *
     * @param array $extensions An array of extensions
     */
    public function setExtensions(array $extensions)
    {
        foreach ($extensions as $extension) {
            $this->addExtension($extension);
        }
    }

    /**
     * Returns all registered extensions.
     *
     * @return array An array of extensions
     */
    public function getExtensions()
    {
        return $this->extensions;
    }

    /**
     * Registers a Token Parser.
     *
     * @param Twig_TokenParserInterface $parser A Twig_TokenParserInterface instance
     */
    public function addTokenParser(Twig_TokenParserInterface $parser)
    {
        if ($this->extensionInitialized) {
            throw new LogicException('Unable to add a token parser as extensions have already been initialized.');
        }

        $this->staging->addTokenParser($parser);
    }

    /**
     * Gets the registered Token Parsers.
     *
     * @return Twig_TokenParserInterface[] An array of Twig_TokenParserInterface
     */
    public function getTokenParsers()
    {
        if (!$this->extensionInitialized) {
            $this->initExtensions();
        }

        return $this->parsers;
    }

    /**
     * Gets registered tags.
     *
     * @return Twig_TokenParserInterface[] An array of Twig_TokenParserInterface instances
     */
    public function getTags()
    {
        $tags = array();
        foreach ($this->getTokenParsers() as $parser) {
            $tags[$parser->getTag()] = $parser;
        }

        return $tags;
    }

    /**
     * Registers a Node Visitor.
     *
     * @param Twig_NodeVisitorInterface $visitor A Twig_NodeVisitorInterface instance
     */
    public function addNodeVisitor(Twig_NodeVisitorInterface $visitor)
    {
        if ($this->extensionInitialized) {
            throw new LogicException('Unable to add a node visitor as extensions have already been initialized.');
        }

        $this->staging->addNodeVisitor($visitor);
    }

    /**
     * Gets the registered Node Visitors.
     *
     * @return Twig_NodeVisitorInterface[] An array of Twig_NodeVisitorInterface instances
     */
    public function getNodeVisitors()
    {
        if (!$this->extensionInitialized) {
            $this->initExtensions();
        }

        return $this->visitors;
    }

    /**
     * Registers a Filter.
     *
     * @param Twig_Filter $filter A Twig_Filter instance
     */
    public function addFilter(Twig_Filter $filter)
    {
        if ($this->extensionInitialized) {
            throw new LogicException(sprintf('Unable to add filter "%s" as extensions have already been initialized.', $filter->getName()));
        }

        $this->staging->addFilter($filter);
    }

    /**
     * Get a filter by name.
     *
     * Subclasses may override this method and load filters differently;
     * so no list of filters is available.
     *
     * @param string $name The filter name
     *
     * @return Twig_Filter|false A Twig_Filter instance or false if the filter does not exist
     */
    public function getFilter($name)
    {
        if (!$this->extensionInitialized) {
            $this->initExtensions();
        }

        if (isset($this->filters[$name])) {
            return $this->filters[$name];
        }

        foreach ($this->filters as $pattern => $filter) {
            $pattern = str_replace('\\*', '(.*?)', preg_quote($pattern, '#'), $count);

            if ($count) {
                if (preg_match('#^'.$pattern.'$#', $name, $matches)) {
                    array_shift($matches);
                    $filter->setArguments($matches);

                    return $filter;
                }
            }
        }

        foreach ($this->filterCallbacks as $callback) {
            if (false !== $filter = $callback($name)) {
                return $filter;
            }
        }

        return false;
    }

    public function registerUndefinedFilterCallback(callable $callable)
    {
        $this->filterCallbacks[] = $callable;
    }

    /**
     * Gets the registered Filters.
     *
     * Be warned that this method cannot return filters defined with registerUndefinedFunctionCallback.
     *
     * @return Twig_Filter[] An array of Twig_Filter instances
     *
     * @see registerUndefinedFilterCallback
     */
    public function getFilters()
    {
        if (!$this->extensionInitialized) {
            $this->initExtensions();
        }

        return $this->filters;
    }

    /**
     * Registers a Test.
     *
     * @param Twig_Test $test A Twig_Test instance
     */
    public function addTest(Twig_Test $test)
    {
        if ($this->extensionInitialized) {
            throw new LogicException(sprintf('Unable to add test "%s" as extensions have already been initialized.', $test->getName()));
        }

        $this->staging->addTest($test);
    }

    /**
     * Gets the registered Tests.
     *
     * @return Twig_Test[] An array of Twig_Test instances
     */
    public function getTests()
    {
        if (!$this->extensionInitialized) {
            $this->initExtensions();
        }

        return $this->tests;
    }

    /**
     * Gets a test by name.
     *
     * @param string $name The test name
     *
     * @return Twig_Test|false A Twig_Test instance or false if the test does not exist
     */
    public function getTest($name)
    {
        if (!$this->extensionInitialized) {
            $this->initExtensions();
        }

        if (isset($this->tests[$name])) {
            return $this->tests[$name];
        }

        return false;
    }

    /**
     * Registers a Function.
     *
     * @param Twig_Function $function A Twig_Function instance
     */
    public function addFunction(Twig_Function $function)
    {
        if ($this->extensionInitialized) {
            throw new LogicException(sprintf('Unable to add function "%s" as extensions have already been initialized.', $function->getName()));
        }

        $this->staging->addFunction($function);
    }

    /**
     * Get a function by name.
     *
     * Subclasses may override this method and load functions differently;
     * so no list of functions is available.
     *
     * @param string $name function name
     *
     * @return Twig_Function|false A Twig_Function instance or false if the function does not exist
     */
    public function getFunction($name)
    {
        if (!$this->extensionInitialized) {
            $this->initExtensions();
        }

        if (isset($this->functions[$name])) {
            return $this->functions[$name];
        }

        foreach ($this->functions as $pattern => $function) {
            $pattern = str_replace('\\*', '(.*?)', preg_quote($pattern, '#'), $count);

            if ($count) {
                if (preg_match('#^'.$pattern.'$#', $name, $matches)) {
                    array_shift($matches);
                    $function->setArguments($matches);

                    return $function;
                }
            }
        }

        foreach ($this->functionCallbacks as $callback) {
            if (false !== $function = $callback($name)) {
                return $function;
            }
        }

        return false;
    }

    public function registerUndefinedFunctionCallback(callable $callable)
    {
        $this->functionCallbacks[] = $callable;
    }

    /**
     * Gets registered functions.
     *
     * Be warned that this method cannot return functions defined with registerUndefinedFunctionCallback.
     *
     * @return Twig_Function[] An array of Twig_Function instances
     *
     * @see registerUndefinedFunctionCallback
     */
    public function getFunctions()
    {
        if (!$this->extensionInitialized) {
            $this->initExtensions();
        }

        return $this->functions;
    }

    /**
     * Registers a Global.
     *
     * New globals can be added before compiling or rendering a template;
     * but after, you can only update existing globals.
     *
     * @param string $name  The global name
     * @param mixed  $value The global value
     */
    public function addGlobal($name, $value)
    {
        if ($this->extensionInitialized || $this->runtimeInitialized) {
            if (null === $this->globals) {
                $this->globals = $this->initGlobals();
            }

            if (!array_key_exists($name, $this->globals)) {
                throw new LogicException(sprintf('Unable to add global "%s" as the runtime or the extensions have already been initialized.', $name));
            }

            // update the value
            $this->globals[$name] = $value;
        } else {
            $this->staging->addGlobal($name, $value);
        }
    }

    /**
     * Gets the registered Globals.
     *
     * @return array An array of globals
     */
    public function getGlobals()
    {
        if (!$this->runtimeInitialized && !$this->extensionInitialized) {
            return $this->initGlobals();
        }

        if (null === $this->globals) {
            $this->globals = $this->initGlobals();
        }

        return $this->globals;
    }

    /**
     * Merges a context with the defined globals.
     *
     * @param array $context An array representing the context
     *
     * @return array The context merged with the globals
     */
    public function mergeGlobals(array $context)
    {
        // we don't use array_merge as the context being generally
        // bigger than globals, this code is faster.
        foreach ($this->getGlobals() as $key => $value) {
            if (!array_key_exists($key, $context)) {
                $context[$key] = $value;
            }
        }

        return $context;
    }

    /**
     * Gets the registered unary Operators.
     *
     * @return array An array of unary operators
     */
    public function getUnaryOperators()
    {
        if (!$this->extensionInitialized) {
            $this->initExtensions();
        }

        return $this->unaryOperators;
    }

    /**
     * Gets the registered binary Operators.
     *
     * @return array An array of binary operators
     */
    public function getBinaryOperators()
    {
        if (!$this->extensionInitialized) {
            $this->initExtensions();
        }

        return $this->binaryOperators;
    }

    private function initGlobals()
    {
        $globals = array();
        foreach ($this->extensions as $extension) {
            if (!$extension instanceof Twig_Extension_GlobalsInterface) {
                continue;
            }

            $extGlob = $extension->getGlobals();
            if (!is_array($extGlob)) {
                throw new UnexpectedValueException(sprintf('"%s::getGlobals()" must return an array of globals.', get_class($extension)));
            }

            $globals[] = $extGlob;
        }

        $globals[] = $this->staging->getGlobals();

        return call_user_func_array('array_merge', $globals);
    }

    private function initExtensions()
    {
        if ($this->extensionInitialized) {
            return;
        }

        $this->extensionInitialized = true;
        $this->parsers = array();
        $this->filters = array();
        $this->functions = array();
        $this->tests = array();
        $this->visitors = array();
        $this->unaryOperators = array();
        $this->binaryOperators = array();

        foreach ($this->extensions as $extension) {
            $this->initExtension($extension);
        }
        $this->initExtension($this->staging);
    }

    private function initExtension(Twig_ExtensionInterface $extension)
    {
        // filters
        foreach ($extension->getFilters() as $filter) {
            $this->filters[$filter->getName()] = $filter;
        }

        // functions
        foreach ($extension->getFunctions() as $function) {
            $this->functions[$function->getName()] = $function;
        }

        // tests
        foreach ($extension->getTests() as $test) {
            $this->tests[$test->getName()] = $test;
        }

        // token parsers
        foreach ($extension->getTokenParsers() as $parser) {
            if (!$parser instanceof Twig_TokenParserInterface) {
                throw new LogicException('getTokenParsers() must return an array of Twig_TokenParserInterface');
            }

            $this->parsers[] = $parser;
        }

        // node visitors
        foreach ($extension->getNodeVisitors() as $visitor) {
            $this->visitors[] = $visitor;
        }

        // operators
        if ($operators = $extension->getOperators()) {
            if (2 !== count($operators)) {
                throw new InvalidArgumentException(sprintf('"%s::getOperators()" does not return a valid operators array.', get_class($extension)));
            }

            $this->unaryOperators = array_merge($this->unaryOperators, $operators[0]);
            $this->binaryOperators = array_merge($this->binaryOperators, $operators[1]);
        }
    }
}
