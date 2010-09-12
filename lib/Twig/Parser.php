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
class Twig_Parser implements Twig_ParserInterface
{
    protected $stream;
    protected $parent;
    protected $handlers;
    protected $visitors;
    protected $expressionParser;
    protected $blocks;
    protected $blockStack;
    protected $macros;
    protected $env;

    public function __construct(Twig_Environment $env = null)
    {
        if (null !== $env) {
            $this->setEnvironment($env);
        }
    }

    public function setEnvironment(Twig_Environment $env)
    {
        $this->env = $env;
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
        // tag handlers
        $this->handlers = $this->env->getTokenParsers();
        $this->handlers->setParser($this);

        // node visitors
        $this->visitors = $this->env->getNodeVisitors();

        if (null === $this->expressionParser) {
            $this->expressionParser = new Twig_ExpressionParser($this);
        }

        $this->stream = $stream;
        $this->parent = null;
        $this->blocks = array();
        $this->macros = array();
        $this->blockStack = array();

        try {
            $body = $this->subparse(null);
        } catch (Twig_SyntaxError $e) {
            if (is_null($e->getFilename())) {
                $e->setFilename($this->stream->getFilename());
            }

            throw $e;
        }

        if (null !== $this->parent) {
            $this->checkBodyNodes($body);
        }

        $node = new Twig_Node_Module($body, $this->parent, new Twig_Node($this->blocks), new Twig_Node($this->macros), $this->stream->getFilename());

        $traverser = new Twig_NodeTraverser($this->env, $this->visitors);

        return $traverser->traverse($node);
    }

    public function subparse($test, $dropNeedle = false)
    {
        $lineno = $this->getCurrentToken()->getLine();
        $rv = array();
        while (!$this->stream->isEOF()) {
            switch ($this->getCurrentToken()->getType()) {
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

                    if ($token->getType() !== Twig_Token::NAME_TYPE) {
                        throw new Twig_SyntaxError('A block must start with a tag name', $token->getLine());
                    }

                    if (!is_null($test) && call_user_func($test, $token)) {
                        if ($dropNeedle) {
                            $this->stream->next();
                        }

                        return new Twig_Node($rv, array(), $lineno);
                    }

                    $subparser = $this->handlers->getTokenParser($token->getValue());
                    if (null === $subparser) {
                        throw new Twig_SyntaxError(sprintf('Unknown tag name "%s"', $token->getValue()), $token->getLine());
                    }

                    $this->stream->next();

                    $node = $subparser->parse($token);
                    if (!is_null($node)) {
                        $rv[] = $node;
                    }
                    break;

                default:
                    throw new LogicException('Lexer or parser ended up in unsupported state.');
            }
        }

        return new Twig_Node($rv, array(), $lineno);
    }

    public function addHandler($name, $class)
    {
        $this->handlers[$name] = $class;
    }

    public function addNodeVisitor(Twig_NodeVisitorInterface $visitor)
    {
        $this->visitors[] = $visitor;
    }

    public function getBlockStack()
    {
        return $this->blockStack;
    }

    public function peekBlockStack()
    {
        return $this->blockStack[count($this->blockStack) - 1];
    }

    public function popBlockStack()
    {
        array_pop($this->blockStack);
    }

    public function pushBlockStack($name)
    {
        $this->blockStack[] = $name;
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
        return $this->parent;
    }

    public function setParent($parent)
    {
        $this->parent = $parent;
    }

    public function getStream()
    {
        return $this->stream;
    }

    public function getCurrentToken()
    {
        return $this->stream->getCurrent();
    }

    protected function checkBodyNodes($body)
    {
        // check that the body only contains block references and empty text nodes
        foreach ($body as $node)
        {
            if (
                ($node instanceof Twig_Node_Text && !preg_match('/^\s*$/s', $node['data']))
                ||
                (!$node instanceof Twig_Node_Text && !$node instanceof Twig_Node_BlockReference)
            ) {
                throw new Twig_SyntaxError('A template that extends another one cannot have a body', $node->getLine(), $this->stream->getFilename());
            }
        }
    }
}
