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
 * @package twig
 * @author  Fabien Potencier <fabien.potencier@symfony-project.com>
 */
class Twig_Environment
{
    const VERSION = '1.0.0-RC2';

    protected $charset;
    protected $loader;
    protected $debug;
    protected $autoReload;
    protected $cache;
    protected $lexer;
    protected $parser;
    protected $compiler;
    protected $baseTemplateClass;
    protected $extensions;
    protected $parsers;
    protected $visitors;
    protected $filters;
    protected $tests;
    protected $functions;
    protected $globals;
    protected $runtimeInitialized;
    protected $loadedTemplates;
    protected $strictVariables;
    protected $unaryOperators;
    protected $binaryOperators;
    protected $templateClassPrefix = '__TwigTemplate_';
    protected $functionCallbacks;
    protected $filterCallbacks;

    /**
     * Constructor.
     *
     * Available options:
     *
     *  * debug: When set to `true`, the generated templates have a __toString()
     *           method that you can use to display the generated nodes (default to
     *           false).
     *
     *  * charset: The charset used by the templates (default to utf-8).
     *
     *  * base_template_class: The base template class to use for generated
     *                         templates (default to Twig_Template).
     *
     *  * cache: An absolute path where to store the compiled templates, or
     *           false to disable compilation cache (default)
     *
     *  * auto_reload: Whether to reload the template is the original source changed.
     *                 If you don't provide the auto_reload option, it will be
     *                 determined automatically base on the debug value.
     *
     *  * strict_variables: Whether to ignore invalid variables in templates
     *                      (default to false).
     *
     *  * autoescape: Whether to enable auto-escaping (default to true);
     *
     *  * optimizations: A flag that indicates which optimizations to apply
     *                   (default to -1 which means that all optimizations are enabled;
     *                   set it to 0 to disable)
     *
     * @param Twig_LoaderInterface   $loader  A Twig_LoaderInterface instance
     * @param array                  $options An array of options
     */
    public function __construct(Twig_LoaderInterface $loader = null, $options = array())
    {
        if (null !== $loader) {
            $this->setLoader($loader);
        }

        $options = array_merge(array(
            'debug'               => false,
            'charset'             => 'UTF-8',
            'base_template_class' => 'Twig_Template',
            'strict_variables'    => false,
            'autoescape'          => true,
            'cache'               => false,
            'auto_reload'         => null,
            'optimizations'       => -1,
        ), $options);

        $this->debug              = (bool) $options['debug'];
        $this->charset            = $options['charset'];
        $this->baseTemplateClass  = $options['base_template_class'];
        $this->autoReload         = null === $options['auto_reload'] ? $this->debug : (bool) $options['auto_reload'];
        $this->extensions         = array(
            'core'      => new Twig_Extension_Core(),
            'escaper'   => new Twig_Extension_Escaper((bool) $options['autoescape']),
            'optimizer' => new Twig_Extension_Optimizer($options['optimizations']),
        );
        $this->strictVariables    = (bool) $options['strict_variables'];
        $this->runtimeInitialized = false;
        $this->setCache($options['cache']);
        $this->functionCallbacks = array();
        $this->filterCallbacks = array();
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
     * @return Boolean true if debug mode is enabled, false otherwise
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
     * @return Boolean true if auto_reload is enabled, false otherwise
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
     * @return Boolean true if strict_variables is enabled, false otherwise
     */
    public function isStrictVariables()
    {
        return $this->strictVariables;
    }

    /**
     * Gets the cache directory or false if cache is disabled.
     *
     * @return string|false
     */
    public function getCache()
    {
        return $this->cache;
    }

     /**
      * Sets the cache directory or false if cache is disabled.
      *
      * @param string|false $cache The absolute path to the compiled templates,
      *                            or false to disable cache
      */
    public function setCache($cache)
    {
        $this->cache = $cache ? $cache : false;
    }

    /**
     * Gets the cache filename for a given template.
     *
     * @param string $name The template name
     *
     * @return string The cache file name
     */
    public function getCacheFilename($name)
    {
        if (false === $this->cache) {
            return false;
        }

        $class = substr($this->getTemplateClass($name), strlen($this->templateClassPrefix));

        return $this->getCache().'/'.substr($class, 0, 2).'/'.substr($class, 2, 2).'/'.substr($class, 4).'.php';
    }

    /**
     * Gets the template class associated with the given string.
     *
     * @param string $name The name for which to calculate the template class name
     *
     * @return string The template class name
     */
    public function getTemplateClass($name)
    {
        return $this->templateClassPrefix.md5($this->loader->getCacheKey($name));
    }

    /**
     * Gets the template class prefix.
     *
     * @return string The template class prefix
     */
    public function getTemplateClassPrefix()
    {
        return $this->templateClassPrefix;
    }

    /**
     * Loads a template by name.
     *
     * @param  string  $name  The template name
     *
     * @return Twig_TemplateInterface A template instance representing the given template name
     */
    public function loadTemplate($name)
    {
        $cls = $this->getTemplateClass($name);

        if (isset($this->loadedTemplates[$cls])) {
            return $this->loadedTemplates[$cls];
        }

        if (!class_exists($cls, false)) {
            if (false === $cache = $this->getCacheFilename($name)) {
                eval('?>'.$this->compileSource($this->loader->getSource($name), $name));
            } else {
                if (!file_exists($cache) || ($this->isAutoReload() && !$this->loader->isFresh($name, filemtime($cache)))) {
                    $this->writeCacheFile($cache, $this->compileSource($this->loader->getSource($name), $name));
                }

                require_once $cache;
            }
        }

        if (!$this->runtimeInitialized) {
            $this->initRuntime();
        }

        return $this->loadedTemplates[$cls] = new $cls($this);
    }

    /**
     * Clears the internal template cache.
     */
    public function clearTemplateCache()
    {
        $this->loadedTemplates = array();
    }

    /**
     * Clears the template cache files on the filesystem.
     */
    public function clearCacheFiles()
    {
        if (false === $this->cache) {
            return;
        }

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->cache), RecursiveIteratorIterator::LEAVES_ONLY) as $file) {
            if ($file->isFile()) {
                @unlink($file->getPathname());
            }
        }
    }

    /**
     * Gets the Lexer instance.
     *
     * @return Twig_LexerInterface A Twig_LexerInterface instance
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
     * @param Twig_LexerInterface A Twig_LexerInterface instance
     */
    public function setLexer(Twig_LexerInterface $lexer)
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
     */
    public function tokenize($source, $name = null)
    {
        return $this->getLexer()->tokenize($source, $name);
    }

    /**
     * Gets the Parser instance.
     *
     * @return Twig_ParserInterface A Twig_ParserInterface instance
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
     * @param Twig_ParserInterface A Twig_ParserInterface instance
     */
    public function setParser(Twig_ParserInterface $parser)
    {
        $this->parser = $parser;
    }

    /**
     * Parses a token stream.
     *
     * @param Twig_TokenStream $tokens A Twig_TokenStream instance
     *
     * @return Twig_Node_Module A Node tree
     */
    public function parse(Twig_TokenStream $tokens)
    {
        return $this->getParser()->parse($tokens);
    }

    /**
     * Gets the Compiler instance.
     *
     * @return Twig_CompilerInterface A Twig_CompilerInterface instance
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
     * @param Twig_CompilerInterface $compiler A Twig_CompilerInterface instance
     */
    public function setCompiler(Twig_CompilerInterface $compiler)
    {
        $this->compiler = $compiler;
    }

    /**
     * Compiles a Node.
     *
     * @param Twig_NodeInterface $node A Twig_NodeInterface instance
     *
     * @return string The compiled PHP source code
     */
    public function compile(Twig_NodeInterface $node)
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
     */
    public function compileSource($source, $name = null)
    {
        return $this->compile($this->parse($this->tokenize($source, $name)));
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
            $extension->initRuntime($this);
        }
    }

    /**
     * Returns true if the given extension is registered.
     *
     * @param string $name The extension name
     *
     * @return Boolean Whether the extension is registered or not
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
        $this->extensions[$extension->getName()] = $extension;
    }

    /**
     * Removes an extension by name.
     *
     * @param string $name The extension name
     */
    public function removeExtension($name)
    {
        unset($this->extensions[$name]);
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
        if (null === $this->parsers) {
            $this->getTokenParsers();
        }

        $this->parsers->addTokenParser($parser);
    }

    /**
     * Gets the registered Token Parsers.
     *
     * @return Twig_TokenParserInterface[] An array of Twig_TokenParserInterface instances
     */
    public function getTokenParsers()
    {
        if (null === $this->parsers) {
            $this->parsers = new Twig_TokenParserBroker;
            foreach ($this->getExtensions() as $extension) {
                $parsers = $extension->getTokenParsers();
                foreach($parsers as $parser) {
                    if ($parser instanceof Twig_TokenParserInterface) {
                        $this->parsers->addTokenParser($parser);
                    } else if ($parser instanceof Twig_TokenParserBrokerInterface) {
                        $this->parsers->addTokenParserBroker($parser);
                    } else {
                        throw new Twig_Error_Runtime('getTokenParsers() must return an array of Twig_TokenParserInterface or Twig_TokenParserBrokerInterface instances');
                    }
                }
            }
        }

        return $this->parsers;
    }

    /**
     * Registers a Node Visitor.
     *
     * @param Twig_NodeVisitorInterface $visitor A Twig_NodeVisitorInterface instance
     */
    public function addNodeVisitor(Twig_NodeVisitorInterface $visitor)
    {
        if (null === $this->visitors) {
            $this->getNodeVisitors();
        }

        $this->visitors[] = $visitor;
    }

    /**
     * Gets the registered Node Visitors.
     *
     * @return Twig_NodeVisitorInterface[] An array of Twig_NodeVisitorInterface instances
     */
    public function getNodeVisitors()
    {
        if (null === $this->visitors) {
            $this->visitors = array();
            foreach ($this->getExtensions() as $extension) {
                $this->visitors = array_merge($this->visitors, $extension->getNodeVisitors());
            }
        }

        return $this->visitors;
    }

    /**
     * Registers a Filter.
     *
     * @param string               $name    The filter name
     * @param Twig_FilterInterface $visitor A Twig_FilterInterface instance
     */
    public function addFilter($name, Twig_FilterInterface $filter)
    {
        if (null === $this->filters) {
            $this->loadFilters();
        }

        $this->filters[$name] = $filter;
    }

    /**
     * Get a filter by name.
     *
     * Subclasses may override this method and load filters differently;
     * so no list of filters is available.
     *
     * @param string $name The filter name
     *
     * @return Twig_Filter|false A Twig_Filter instance or false if the filter does not exists
     */
    public function getFilter($name)
    {
        if (null === $this->filters) {
            $this->loadFilters();
        }

        if (isset($this->filters[$name])) {
            return $this->filters[$name];
        }

        foreach ($this->filterCallbacks as $callback) {
            if (false !== $filter = call_user_func($callback, $name)) {
                return $filter;
            }
        }

        return false;
    }

    public function registerUndefinedFilterCallback($callable)
    {
        $this->filterCallbacks[] = $callable;
    }

    /**
     * Gets the registered Filters.
     *
     * @return Twig_FilterInterface[] An array of Twig_FilterInterface instances
     */
    protected function loadFilters()
    {
        $this->filters = array();
        foreach ($this->getExtensions() as $extension) {
            $this->filters = array_merge($this->filters, $extension->getFilters());
        }
    }

    /**
     * Registers a Test.
     *
     * @param string             $name    The test name
     * @param Twig_TestInterface $visitor A Twig_TestInterface instance
     */
    public function addTest($name, Twig_TestInterface $test)
    {
        if (null === $this->tests) {
            $this->getTests();
        }

        $this->tests[$name] = $test;
    }

    /**
     * Gets the registered Tests.
     *
     * @return Twig_TestInterface[] An array of Twig_TestInterface instances
     */
    public function getTests()
    {
        if (null === $this->tests) {
            $this->tests = array();
            foreach ($this->getExtensions() as $extension) {
                $this->tests = array_merge($this->tests, $extension->getTests());
            }
        }

        return $this->tests;
    }

    /**
     * Registers a Function.
     *
     * @param string                 $name    The function name
     * @param Twig_FunctionInterface $visitor A Twig_FunctionInterface instance
     */
    public function addFunction($name, Twig_FunctionInterface $function)
    {
        if (null === $this->functions) {
            $this->loadFunctions();
        }

        $this->functions[$name] = $function;
    }

    /**
     * Get a function by name.
     *
     * Subclasses may override this method and load functions differently;
     * so no list of functions is available.
     *
     * @param string $name function name
     *
     * @return Twig_Function|false A Twig_Function instance or false if the function does not exists
     */
    public function getFunction($name)
    {
        if (null === $this->functions) {
            $this->loadFunctions();
        }

        if (isset($this->functions[$name])) {
            return $this->functions[$name];
        }

        foreach ($this->functionCallbacks as $callback) {
            if (false !== $function = call_user_func($callback, $name)) {
                return $function;
            }
        }

        return false;
    }

    public function registerUndefinedFunctionCallback($callable)
    {
        $this->functionCallbacks[] = $callable;
    }

    protected function loadFunctions() {
        $this->functions = array();
        foreach ($this->getExtensions() as $extension) {
            $this->functions = array_merge($this->functions, $extension->getFunctions());
        }
    }

    /**
     * Registers a Global.
     *
     * @param string $name  The global name
     * @param mixed  $value The global value
     */
    public function addGlobal($name, $value)
    {
        if (null === $this->globals) {
            $this->getGlobals();
        }

        $this->globals[$name] = $value;
    }

    /**
     * Gets the registered Globals.
     *
     * @return array An array of globals
     */
    public function getGlobals()
    {
        if (null === $this->globals) {
            $this->globals = array();
            foreach ($this->getExtensions() as $extension) {
                $this->globals = array_merge($this->globals, $extension->getGlobals());
            }
        }

        return $this->globals;
    }

    /**
     * Gets the registered unary Operators.
     *
     * @return array An array of unary operators
     */
    public function getUnaryOperators()
    {
        if (null === $this->unaryOperators) {
            $this->initOperators();
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
        if (null === $this->binaryOperators) {
            $this->initOperators();
        }

        return $this->binaryOperators;
    }

    protected function initOperators()
    {
        $this->unaryOperators = array();
        $this->binaryOperators = array();
        foreach ($this->getExtensions() as $extension) {
            $operators = $extension->getOperators();

            if (!$operators) {
                continue;
            }

            if (2 !== count($operators)) {
                throw new InvalidArgumentException(sprintf('"%s::getOperators()" does not return a valid operators array.', get_class($extension)));
            }

            $this->unaryOperators = array_merge($this->unaryOperators, $operators[0]);
            $this->binaryOperators = array_merge($this->binaryOperators, $operators[1]);
        }
    }

    protected function writeCacheFile($file, $content)
    {
        if (!is_dir(dirname($file))) {
            mkdir(dirname($file), 0777, true);
        }

        $tmpFile = tempnam(dirname($file), basename($file));
        if (false !== @file_put_contents($tmpFile, $content)) {
            // rename does not work on Win32 before 5.2.6
            if (@rename($tmpFile, $file) || (@copy($tmpFile, $file) && unlink($tmpFile))) {
                chmod($file, 0644);

                return;
            }
        }

        throw new Twig_Error_Runtime(sprintf('Failed to write cache file "%s".', $file));
    }
}
