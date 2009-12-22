<?php

/*
 * This file is part of Twig.
 *
 * (c) 2009 Fabien Potencier
 * (c) 2009 Armin Ronacher
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Twig_Parser
{
  protected $stream;
  protected $extends;
  protected $handlers;
  protected $visitors;
  protected $expressionParser;
  protected $blocks;
  protected $currentBlock;
  protected $macros;
  protected $env;

  public function __construct(Twig_Environment $env = null)
  {
    $this->setEnvironment($env);
  }

  public function setEnvironment(Twig_Environment $env)
  {
    $this->env = $env;

    $this->handlers = array();
    $this->visitors = array();

    // tag handlers
    foreach ($this->env->getTokenParsers() as $handler)
    {
      $handler->setParser($this);

      $this->handlers[$handler->getTag()] = $handler;
    }

    // node visitors
    $this->visitors = $env->getNodeVisitors();
  }

  /**
   * Converts a token stream to a node tree.
   *
   * @param  Twig_TokenStream $stream A token stream instance
   *
   * @return Twig_Node_Module A node tree
   */
  public function parse(Twig_TokenStream $stream)
  {
    if (null === $this->expressionParser)
    {
      $this->expressionParser = new Twig_ExpressionParser($this);
    }

    $this->stream = $stream;
    $this->extends = null;
    $this->blocks = array();
    $this->macros = array();
    $this->currentBlock = null;

    try
    {
      $body = $this->subparse(null);
    }
    catch (Twig_SyntaxError $e)
    {
      if (is_null($e->getFilename()))
      {
        $e->setFilename($this->stream->getFilename());
      }

      throw $e;
    }

    if (!is_null($this->extends))
    {
      foreach ($this->blocks as $block)
      {
        $block->setParent($this->extends);
      }
    }

    $node = new Twig_Node_Module($body, $this->extends, $this->blocks, $this->macros, $this->stream->getFilename());

    $t = new Twig_NodeTraverser($this->env);
    foreach ($this->visitors as $visitor)
    {
      $node = $t->traverse($node, $visitor);
    }

    return $node;
  }

  public function subparse($test, $drop_needle = false)
  {
    $lineno = $this->getCurrentToken()->getLine();
    $rv = array();
    while (!$this->stream->isEOF())
    {
      switch ($this->getCurrentToken()->getType())
      {
        case Twig_Token::TEXT_TYPE:
          $token = $this->stream->next();
          $rv[] = new Twig_Node_Text($token->getValue(), $token->getLine());
          break;

        case Twig_Token::VAR_START_TYPE:
          $token = $this->stream->next();
          $expr = $this->expressionParser->parseExpression();
          $this->stream->expect(Twig_Token::VAR_END_TYPE);
          $rv[] = new Twig_Node_Print($expr, $token->getLine());
          break;

        case Twig_Token::BLOCK_START_TYPE:
          $this->stream->next();
          $token = $this->getCurrentToken();

          if ($token->getType() !== Twig_Token::NAME_TYPE)
          {
            throw new Twig_SyntaxError('A block must start with a tag name', $token->getLine());
          }

          if (!is_null($test) && call_user_func($test, $token))
          {
            if ($drop_needle)
            {
              $this->stream->next();
            }

            return new Twig_NodeList($rv, $lineno);
          }

          if (!isset($this->handlers[$token->getValue()]))
          {
            throw new Twig_SyntaxError(sprintf('Unknown tag name "%s"', $token->getValue()), $token->getLine());
          }

          $this->stream->next();

          $subparser = $this->handlers[$token->getValue()];
          $node = $subparser->parse($token);
          if (!is_null($node))
          {
            $rv[] = $node;
          }
          break;

        default:
          throw new LogicException('Lexer or parser ended up in unsupported state.');
      }
    }

    return new Twig_NodeList($rv, $lineno);
  }

  public function addHandler($name, $class)
  {
    $this->handlers[$name] = $class;
  }

  public function addNodeVisitor(Twig_NodeVisitorInterface $visitor)
  {
    $this->visitors[] = $visitor;
  }

  public function getCurrentBlock()
  {
    return $this->currentBlock;
  }

  public function setCurrentBlock($name)
  {
    $this->currentBlock = $name;
  }

  public function hasBlock($name)
  {
    return isset($this->blocks[$name]);
  }

  public function setBlock($name, $value)
  {
    $this->blocks[$name] = $value;
  }

  public function hasMacro($name)
  {
    return isset($this->macros[$name]);
  }

  public function setMacro($name, $value)
  {
    $this->macros[$name] = $value;
  }

  public function getExpressionParser()
  {
    return $this->expressionParser;
  }

  public function getParent()
  {
    return $this->extends;
  }

  public function setParent($extends)
  {
    $this->extends = $extends;
  }

  public function getStream()
  {
    return $this->stream;
  }

  public function getCurrentToken()
  {
    return $this->stream->getCurrent();
  }
}
