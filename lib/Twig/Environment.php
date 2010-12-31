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
    const VERSION = '1.0.0-BETA1';

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
        if ($options['cache']) {
            $this->setCache($options['cache']);
        }
    }

    public function getBaseTemplateClass()
    {
        return $this->baseTemplateClass;
    }

    public function setBaseTemplateClass($class)
    {
        $this->baseTemplateClass = $class;
    }

    public function enableDebug()
    {
        $this->debug = true;
    }

    public function disableDebug()
    {
        $this->debug = false;
    }

    public function isDebug()
    {
        return $this->debug;
    }

    public function isAutoReload()
    {
        return $this->autoReload;
    }

    public function setAutoReload($autoReload)
    {
        $this->autoReload = (Boolean) $autoReload;
    }

    public function enableStrictVariables()
    {
        $this->strictVariables = true;
    }

    public function disableStrictVariables()
    {
        $this->strictVariables = false;
    }

    public function isStrictVariables()
    {
        return $this->strictVariables;
    }

    public function getCache()
    {
        return $this->cache;
    }

    public function setCache($cache)
    {
        $this->cache = $cache;

        if ($this->cache && !is_dir($this->cache)) {
            mkdir($this->cache, 0777, true);
        }
    }

    public function getCacheFilename($name)
    {
        return $this->getCache() ? $this->getCache().'/'.$this->getTemplateClass($name).'.php' : false;
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

    public function clearTemplateCache()
    {
        $this->loadedTemplates = array();
    }

    /**
     * Clears the template cache files on the filesystem.
     */
    public function clearCacheFiles()
    {
        if ($this->cache) {
            foreach(new DirectoryIterator($this->cache) as $fileInfo) {
                if (0 === strpos($fileInfo->getFilename(), $this->templateClassPrefix)) {
                    @unlink($fileInfo->getPathname());
                }
            }
        }
    }

    public function getLexer()
    {
        if (null === $this->lexer) {
            $this->lexer = new Twig_Lexer($this);
        }

        return $this->lexer;
    }

    public function setLexer(Twig_LexerInterface $lexer)
    {
        $this->lexer = $lexer;
    }

    public function tokenize($source, $name = null)
    {
        return $this->getLexer()->tokenize($source, $name);
    }

    public function getParser()
    {
        if (null === $this->parser) {
            $this->parser = new Twig_Parser($this);
        }

        return $this->parser;
    }

    public function setParser(Twig_ParserInterface $parser)
    {
        $this->parser = $parser;
    }

    public function parse(Twig_TokenStream $tokens)
    {
        return $this->getParser()->parse($tokens);
    }

    public function getCompiler()
    {
        if (null === $this->compiler) {
            $this->compiler = new Twig_Compiler($this);
        }

        return $this->compiler;
    }

    public function setCompiler(Twig_CompilerInterface $compiler)
    {
        $this->compiler = $compiler;
    }

    public function compile(Twig_NodeInterface $node)
    {
        return $this->getCompiler()->compile($node)->getSource();
    }

    public function compileSource($source, $name = null)
    {
        return $this->compile($this->parse($this->tokenize($source, $name)));
    }

    public function setLoader(Twig_LoaderInterface $loader)
    {
        $this->loader = $loader;
    }

    public function getLoader()
    {
        return $this->loader;
    }

    public function setCharset($charset)
    {
        $this->charset = $charset;
    }

    public function getCharset()
    {
        return $this->charset;
    }

    public function initRuntime()
    {
        $this->runtimeInitialized = true;

        foreach ($this->getExtensions() as $extension) {
            $extension->initRuntime($this);
        }
    }

    public function hasExtension($name)
    {
        return isset($this->extensions[$name]);
    }

    public function getExtension($name)
    {
        if (!isset($this->extensions[$name])) {
            throw new Twig_Error_Runtime(sprintf('The "%s" extension is not enabled.', $name));
        }

        return $this->extensions[$name];
    }

    public function addExtension(Twig_ExtensionInterface $extension)
    {
        $this->extensions[$extension->getName()] = $extension;
    }

    public function removeExtension($name)
    {
        unset($this->extensions[$name]);
    }

    public function setExtensions(array $extensions)
    {
        foreach ($extensions as $extension) {
            $this->addExtension($extension);
        }
    }

    public function getExtensions()
    {
        return $this->extensions;
    }

    public function addTokenParser(Twig_TokenParserInterface $parser)
    {
        if (null === $this->parsers) {
            $this->getTokenParsers();
        }

        $this->parsers->addTokenParser($parser);
    }

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

    public function addNodeVisitor(Twig_NodeVisitorInterface $visitor)
    {
        if (null === $this->visitors) {
            $this->getNodeVisitors();
        }

        $this->visitors[] = $visitor;
    }

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

    public function addFilter($name, Twig_FilterInterface $filter)
    {
        if (null === $this->filters) {
            $this->getFilters();
        }

        $this->filters[$name] = $filter;
    }

    public function getFilters()
    {
        if (null === $this->filters) {
            $this->filters = array();
            foreach ($this->getExtensions() as $extension) {
                $this->filters = array_merge($this->filters, $extension->getFilters());
            }
        }

        return $this->filters;
    }

    public function addTest($name, Twig_TestInterface $test)
    {
        if (null === $this->tests) {
            $this->getTests();
        }

        $this->tests[$name] = $test;
    }

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

    public function addFunction($name, Twig_Function $function)
    {
        if (null === $this->functions) {
            $this->loadFunctions();
        }
        $this->functions[$name] = $function;
    }

    /**
     * Get a function by name
     *
     * Subclasses may override getFunction($name) and load functions differently;
     * so no list of functions is available.
     *
     * @param string $name function name
     * @return Twig_Function|null A Twig_Function instance or null if the function does not exists
     */
    public function getFunction($name)
    {
        if (null === $this->functions) {
            $this->loadFunctions();
        }

        if (isset($this->functions[$name])) {
            return $this->functions[$name];
        }

        return null;
    }

    protected function loadFunctions() {
        $this->functions = array();
        foreach ($this->getExtensions() as $extension) {
            $this->functions = array_merge($this->functions, $extension->getFunctions());
        }
    }

    public function addGlobal($name, $value)
    {
        if (null === $this->globals) {
            $this->getGlobals();
        }

        $this->globals[$name] = $value;
    }

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

    public function getUnaryOperators()
    {
        if (null === $this->unaryOperators) {
            $this->initOperators();
        }

        return $this->unaryOperators;
    }

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
