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
  const VERSION = '0.9.5-DEV';

  protected $charset;
  protected $loader;
  protected $trimBlocks;
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
  protected $runtimeInitialized;
  protected $loadedTemplates;

  /**
   * Constructor.
   *
   * Available options:
   *
   *  * debug: When set to `true`, the generated templates have a __toString()
   *    method that you can use to display the generated nodes (default to
   *    false).
   *
   *  * trim_blocks: Mimicks the behavior of PHP by removing the newline that
   *    follows instructions if present (default to false).
   *
   *  * charset: The charset used by the templates (default to utf-8).
   *
   *  * base_template_class: The base template class to use for generated
   *    templates (default to Twig_Template).
   *
   *  * cache: Can be one of three values:
   *             * null (the default): Twig will create a sub-directory under the system tmp directory
   *               (not recommended as templates from two projects with the same name will share the cache)
   *             * false: disable the compile cache altogether
   *             * An absolute path where to store the compiled templates
   *
   *  * auto_reload: Whether to reload the template is the original source changed.
   *    If you don't provide the auto_reload option, it will be
   *    determined automatically base on the debug value.
   */
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
    $this->autoReload         = isset($options['auto_reload']) ? (bool) $options['auto_reload'] : $this->debug;
    $this->extensions         = array(new Twig_Extension_Core());
    $this->runtimeInitialized = false;
    $this->setCache(isset($options['cache']) ? $options['cache'] : null);
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

  public function getCache()
  {
    return $this->cache;
  }

  public function setCache($cache)
  {
    $this->cache = null === $cache ? sys_get_temp_dir().DIRECTORY_SEPARATOR.'twig_'.md5(dirname(__FILE__)) : $cache;

    if (false !== $this->cache && !is_dir($this->cache))
    {
      mkdir($this->cache, 0755, true);
    }
  }

  public function getCacheFilename($name)
  {
    return $this->getCache() ? $this->getCache().'/'.$this->getTemplateClass($name).'.php' : false;
  }

  public function getTrimBlocks()
  {
    return $this->trimBlocks;
  }

  public function setTrimBlocks($bool)
  {
    $this->trimBlocks = (bool) $bool;
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
    return '__TwigTemplate_'.md5($this->loader->getCacheKey($name));
  }

  /**
   * Loads a template by name.
   *
   * @param  string $name The template name
   *
   * @return Twig_TemplateInterface A template instance representing the given template name
   */
  public function loadTemplate($name)
  {
    $cls = $this->getTemplateClass($name);

    if (isset($this->loadedTemplates[$cls]))
    {
      return $this->loadedTemplates[$cls];
    }

    if (!class_exists($cls, false))
    {
      if (false === $cache = $this->getCacheFilename($name))
      {
        eval('?>'.$this->compileSource($this->loader->getSource($name), $name));
      }
      else
      {
        if (!file_exists($cache) || ($this->isAutoReload() && !$this->loader->isFresh($name, filemtime($cache))))
        {
          $content = $this->compileSource($this->loader->getSource($name), $name);

          if (false === file_put_contents($cache, $content, LOCK_EX))
          {
            eval('?>'.$content);
          }
          else
          {
            require_once $cache;
          }
        }
        else
        {
          require_once $cache;
        }
      }
    }

    if (!$this->runtimeInitialized)
    {
      $this->initRuntime();

      $this->runtimeInitialized = true;
    }

    return $this->loadedTemplates[$cls] = new $cls($this);
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

  public function tokenize($source, $name)
  {
    return $this->getLexer()->tokenize($source, $name);
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

  public function compileSource($source, $name)
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

  public function getNodeVisitors()
  {
    if (null === $this->visitors)
    {
      $this->visitors = array();
      foreach ($this->getExtensions() as $extension)
      {
        $this->visitors = array_merge($this->visitors, $extension->getNodeVisitors());
      }
    }

    return $this->visitors;
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
