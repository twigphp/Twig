Hacking Twig
============

Twig is very extensible and you can easily hack it. Keep in mind that you
should probably try to create an extension before hacking the core, as most
features and enhancements can be done with extensions. This chapter is also
useful for people who want to understand how Twig works under the hood.

How Twig works?
---------------

The rendering of a Twig template can be summarized into four key steps:

* **Load** the template: If the template is already compiled, load it and go
  to the *evaluation* step, otherwise:

  * First, the **lexer** tokenizes the template source code into small pieces
    for easier processing;
  * Then, the **parser** converts the token stream into a meaningful tree
    of nodes (the Abstract Syntax Tree);
  * Eventually, the *compiler* transforms the AST into PHP code;

* **Evaluate** the template: It basically means calling the ``display()``
  method of the compiled template and passing it the context.

The Lexer
---------

The Twig lexer goal is to tokenize a source code into a token stream (each
token is of class ``Token``, and the stream is an instance of
``Twig_TokenStream``). The default lexer recognizes nine different token types:

* ``Twig_Token::TEXT_TYPE``
* ``Twig_Token::BLOCK_START_TYPE``
* ``Twig_Token::VAR_START_TYPE``
* ``Twig_Token::BLOCK_END_TYPE``
* ``Twig_Token::VAR_END_TYPE``
* ``Twig_Token::NAME_TYPE``
* ``Twig_Token::NUMBER_TYPE``
* ``Twig_Token::STRING_TYPE``
* ``Twig_Token::OPERATOR_TYPE``
* ``Twig_Token::EOF_TYPE``

You can manually convert a source code into a token stream by calling the
``tokenize()`` of an environment::

    $stream = $twig->tokenize($source, $identifier);

As the stream has a ``__toString()`` method, you can have a textual
representation of it by echoing the object::

    echo $stream."\n";

Here is the output for the ``Hello {{ name }}`` template:

.. code-block:: text

    TEXT_TYPE(Hello )
    VAR_START_TYPE()
    NAME_TYPE(name)
    VAR_END_TYPE()
    EOF_TYPE()

You can change the default lexer use by Twig (``Twig_Lexer``) by calling the
``setLexer()`` method::

    $twig->setLexer($lexer);

Lexer classes must implement the ``Twig_LexerInterface``::

    interface Twig_LexerInterface
    {
        /**
         * Tokenizes a source code.
         *
         * @param  string $code     The source code
         * @param  string $filename A unique identifier for the source code
         *
         * @return Twig_TokenStream A token stream instance
         */
        function tokenize($code, $filename = 'n/a');
    }

The Parser
----------

The parser converts the token stream into an AST (Abstract Syntax Tree), or a
node tree (of class ``Twig_Node_Module``). The core extension defines the
basic nodes like: ``for``, ``if``, ... and the expression nodes.

You can manually convert a token stream into a node tree by calling the
``parse()`` method of an environment::

    $nodes = $twig->parse($stream);

Echoing the node object gives you a nice representation of the tree::

    echo $nodes."\n";

Here is the output for the ``Hello {{ name }}`` template:

.. code-block:: text

    Twig_Node_Module(
      Twig_Node_Text(Hello )
      Twig_Node_Print(
        Twig_Node_Expression_Name(name)
      )
    )

The default parser (``Twig_TokenParser``) can be also changed by calling the
``setParser()`` method::

    $twig->setParser($parser);

All Twig parsers must implement the ``Twig_ParserInterface``::

    interface Twig_ParserInterface
    {
        /**
         * Converts a token stream to a node tree.
         *
         * @param  Twig_TokenStream $stream A token stream instance
         *
         * @return Twig_Node_Module A node tree
         */
        function parser(Twig_TokenStream $code);
    }

The Compiler
------------

The last step is done by the compiler. It takes a node tree as an input and
generates PHP code usable for runtime execution of the templates. The default
compiler generates PHP classes to ease the implementation of the template
inheritance feature.

You can call the compiler by hand with the ``compile()`` method of an
environment::

    $php = $twig->compile($nodes);

The ``compile()`` method returns the PHP source code representing the node.

The generated template for a ``Hello {{ name }}`` template reads as follows::

    /* Hello {{ name }} */
    class __TwigTemplate_1121b6f109fe93ebe8c6e22e3712bceb extends Twig_Template
    {
        public function display($context)
        {
            $this->env->initRuntime();

            // line 1
            echo "Hello ";
            echo (isset($context['name']) ? $context['name'] : null);
        }
    }

As for the lexer and the parser, the default compiler (``Twig_Compiler``) can
be changed by calling the ``setCompiler()`` method::

    $twig->setCompiler($compiler);

All Twig compilers must implement the ``Twig_CompilerInterface``::

    interface Twig_CompilerInterface
    {
        /**
         * Compiles a node.
         *
         * @param  Twig_Node $node The node to compile
         *
         * @return Twig_Compiler The current compiler instance
         */
        function compile(Twig_Node $node);

        /**
         * Gets the current PHP code after compilation.
         *
         * @return string The PHP code
         */
        function getSource();
    }
