Extending Twig
==============

Twig supports extensions that can add extra tags, filters, or even extend the
parser itself with node transformer classes. The main motivation for writing
an extension is to move often used code into a reusable class like adding
support for internationalization.

Most of the time, it is useful to create a single extension for your project,
to host all the specific tags and filters you want to add to Twig.

Anatomy of an Extension
-----------------------

An extension is a class that implements the `Twig_ExtensionInterface`:

    [php]
    interface Twig_ExtensionInterface
    {
      /**
       * Initializes the runtime environment.
       *
       * This is where you can load some file that contains filter functions for instance.
       */
      public function initRuntime();

      /**
       * Returns the token parser instances to add to the existing list.
       *
       * @return array An array of Twig_TokenParser instances
       */
      public function getTokenParsers();

      /**
       * Returns the node transformer instances to add to the existing list.
       *
       * @return array An array of Twig_NodeTransformer instances
       */
      public function getNodeTransformers();

      /**
       * Returns a list of filters to add to the existing list.
       *
       * @return array An array of filters
       */
      public function getFilters();

      /**
       * Returns the name of the extension.
       *
       * @return string The extension name
       */
      public function getName();
    }

Instead of you implementing the whole interface, your extension class can
inherit from the `Twig_Extension` class, which provides empty implementations
of all the above methods to keep your extension clean.

>**TIP**
>The bundled extensions are great examples of how extensions work.

Defining new Filters
--------------------

The most common element you will want to add to Twig is filters. A filter is
just a regular PHP callable that takes the left side of the filter as first
argument and the arguments passed to the filter as extra arguments.

Let's create a filter, named `rot13`, which returns the
[rot13](http://www.php.net/manual/en/function.str-rot13.php) transformation of
a string:

    [twig]
    {{ "Twig"|rot13 }}

    {# should displays Gjvt #}

Here is the simplest extension class you can create to add this filter:

    [php]
    class Project_Twig_Extension extends Twig_Extension
    {
      public function getFilters()
      {
        return array(
          'rot13' => array('str_rot13', false),
        );
      }

      public function getName()
      {
        return 'project';
      }
    }

Registering the new extension is like registering core extensions:

    [php]
    $twig->addExtension(new Project_Twig_Extension());

Filters can also be passed the current environment. You might have noticed
that a filter is defined by a callable and a Boolean. If you change the
Boolean to `true`, Twig will pass the current environment as the first
argument to the filter call:

    [php]
    class Project_Twig_Extension extends Twig_Extension
    {
      public function getFilters()
      {
        return array(
          'rot13' => array('twig_compute_rot13', true),
        );
      }

      // ...
    }

    function twig_compute_rot13(Twig_Environment $env, $string)
    {
      // get the current charset for instance
      $charset = $env->getCharset();

      return str_rot13($string);
    }

Defining new Tags
-----------------

One of the most exiting feature of a template engine like Twig is the
possibility to define new language constructs.

Let's create a simple `set` tag that allows the definition of simple variables
from within a template. The tag can be used like follows:

    [twig]
    {% set name "value" %}

    {{ name }}

    {# should output value #}

First, we need to create a `Twig_TokenParser` class which will be able to
parse this new language construct:

    [php]
    class Project_Twig_Set_TokenParser extends Twig_TokenParser
    {
      // ...
    }

Of course, we need to register this token parser in our extension class:

    [php]
    class Project_Twig_Extension extends Twig_Extension
    {
      public function getTokenParsers()
      {
        return array(new Project_Twig_Set_TokenParser());
      }

      // ...
    }

Now, let's see the actual code of the token parser class:

    [php]
    class Project_Twig_Set_TokenParser extends Twig_TokenParser
    {
      public function parse(Twig_Token $token)
      {
        $lineno = $token->getLine();
        $name = $this->parser->getStream()->expect(Twig_Token::NAME_TYPE)->getValue();
        $value = $this->parser->getExpressionParser()->parseExpression();

        $this->parser->getStream()->expect(Twig_Token::BLOCK_END_TYPE);

        return new Project_Twig_Set_Node($name, $value, $lineno, $this->getTag());
      }

      public function getTag()
      {
        return 'set';
      }
    }

The `getTag()` method must return the tag we want to parse, here `set`. The
`parse()` method is invoked whenever the parser encounters a `set` tag. It
should return a `Twig_Node` instance that represents the node. The parsing
process is simplified thanks to a bunch of methods you can call from the token
stream (`$this->parser->getStream()`):

 * `test()`: Tests the type and optionally the value of the next token and
   returns it.

 * `expect()`: Expects a token and returns it (like `test()`) or throw a
   syntax error if not found.

 * `look()`: Looks a the next token. This is how you can have a look at the
   next token without consume it.

Parsing expressions is done by calling the `parseExpression()` like we did for
the `set` tag.

>**TIP**
>Reading the existing `TokenParser` classes is the best way to learn all the
>nitty-gritty details of the parsing process.

The `Project_Twig_Set_Node` class itself is rather simple:

    [php]
    class Project_Twig_Set_Node extends Twig_Node
    {
      protected $name;
      protected $value;

      public function __construct($name, Twig_Node_Expression $value, $lineno)
      {
        parent::__construct($lineno);

        $this->name = $name;
        $this->value = $value;
      }

      public function compile($compiler)
      {
        $compiler
          ->addDebugInfo($this)
          ->write('$context[\''.$this->name.'\'] = ')
          ->subcompile($this->value)
          ->raw(";\n")
        ;
      }
    }

The compiler implements a fluid interface and provides methods that helps the
developer generate beautiful and readable PHP code:

 * `subcompile()`: Compiles a node.

 * `raw()`: Writes the given string as is.

 * `write()`: Writes the given string by adding indentation at the beginning
   of each line.

 * `string()`: Writes a quoted string.

 * `repr()`: Writes a PHP representation of a given value (see `Twig_Node_For`
   for a usage example).

 * `pushContext()`: Pushes the current context on the stack (see
   `Twig_Node_For` for a usage example).

 * `popContext()`: Pops a context from the stack (see `Twig_Node_For` for a
   usage example).

 * `addDebugInfo()`: Adds the line of the original template file related to
   the current node as a comment.

 * `indent()`: Indents the generated code (see `Twig_Node_Block` for a usage
   example).

 * `outdent()`: Outdents the generated code (see `Twig_Node_Block` for a usage
   example).

Creating a Node Transformer
---------------------------

To be written...
