<?php

/*
 * This file is part of Twig.
 *
 * (c) 2009 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Twig_Environment
{
  const VERSION = '0.9.2-DEV';

  protected $charset;
  protected $loader;
  protected $trimBlocks;
  protected $debug;
  protected $lexer;
  protected $parser;
  protected $compiler;
  protected $baseTemplateClass;
  protected $extensions;
  protected $parsers;
  protected $transformers;
  protected $filters;
  protected $runtimeInitialized;
  protected $loadedTemplates;

  public function __construct(Twig_LoaderInterface $loader = null, $options = array())
  {
    if (null !== $loader)
    {
      $this->setLoader($loader);
    }

    $this->debug              = isset($options['debug']) ? (bool) $options['debug'] : false;
    $this->trimBlocks         = isset($options['trim_blocks']) ? (bool) $options['trim_blocks'] : false;
    $this->charset            = isset($options['charset']) ? $options['charset'] : 'UTF-8';
    $this->baseTemplateClass  = isset($options['base_template_class']) ? $options['base_template_class'] : 'Twig_Template';
    $this->extensions         = array(new Twig_Extension_Core());
    $this->runtimeInitialized = false;
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

  public function getTrimBlocks()
  {
    return $this->trimBlocks;
  }

  public function setTrimBlocks($bool)
  {
    $this->trimBlocks = (bool) $bool;
  }

  public function loadTemplate($name)
  {
    if (!$this->runtimeInitialized)
    {
      $this->initRuntime();

      $this->runtimeInitialized = true;
    }

    if (isset($this->loadedTemplates[$name]))
    {
      return $this->loadedTemplates[$name];
    }

    $cls = $this->getLoader()->load($name, $this);

    return $this->loadedTemplates[$name] = new $cls($this);
  }

  public function clearTemplateCache()
  {
    $this->loadedTemplates = array();
  }

  public function getLexer()
  {
    if (null === $this->lexer)
    {
      $this->lexer = new Twig_Lexer($this);
    }

    return $this->lexer;
  }

  public function setLexer(Twig_LexerInterface $lexer)
  {
    $this->lexer = $lexer;
    $lexer->setEnvironment($this);
  }

  public function tokenize($source, $name = null)
  {
    return $this->getLexer()->tokenize($source, null === $name ? $source : $name);
  }

  public function getParser()
  {
    if (null === $this->parser)
    {
      $this->parser = new Twig_Parser($this);
    }

    return $this->parser;
  }

  public function setParser(Twig_ParserInterface $parser)
  {
    $this->parser = $parser;
    $parser->setEnvironment($this);
  }

  public function parse(Twig_TokenStream $tokens)
  {
    return $this->getParser()->parse($tokens);
  }

  public function getCompiler()
  {
    if (null === $this->compiler)
    {
      $this->compiler = new Twig_Compiler($this);
    }

    return $this->compiler;
  }

  public function setCompiler(Twig_CompilerInterface $compiler)
  {
    $this->compiler = $compiler;
    $compiler->setEnvironment($this);
  }

  public function compile(Twig_Node $node)
  {
    return $this->getCompiler()->compile($node)->getSource();
  }

  public function setLoader(Twig_LoaderInterface $loader)
  {
    $this->loader = $loader;
    $loader->setEnvironment($this);
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
    foreach ($this->getExtensions() as $extension)
    {
      $extension->initRuntime();
    }
  }

  public function hasExtension($name)
  {
    return isset($this->extensions[$name]);
  }

  public function getExtension($name)
  {
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
    foreach ($extensions as $extension)
    {
      $this->addExtension($extension);
    }
  }

  public function getExtensions()
  {
    return $this->extensions;
  }

  public function getTokenParsers()
  {
    if (null === $this->parsers)
    {
      $this->parsers = array();
      foreach ($this->getExtensions() as $extension)
      {
        $this->parsers = array_merge($this->parsers, $extension->getTokenParsers());
      }
    }

    return $this->parsers;
  }

  public function getNodeTransformers()
  {
    if (null === $this->transformers)
    {
      $this->transformers = array();
      foreach ($this->getExtensions() as $extension)
      {
        $this->transformers = array_merge($this->transformers, $extension->getNodeTransformers());
      }
    }

    return $this->transformers;
  }

  public function getFilters()
  {
    if (null === $this->filters)
    {
      $this->filters = array();
      foreach ($this->getExtensions() as $extension)
      {
        $this->filters = array_merge($this->filters, $extension->getFilters());
      }
    }

    return $this->filters;
  }
}
