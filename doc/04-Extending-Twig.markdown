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

The `getName()` method must always be implemented to return a unique
identifier for the extension. Here is the most basic extension you can create:

    [php]
    class Project_Twig_Extension extends Twig_Extension
    {
      public function getName()
      {
        return 'project';
      }
    }

>**TIP**
>The bundled extensions are great examples of how extensions work.

Registering a custom extension is like registering any other extension:

    [php]
    $twig->addExtension(new Project_Twig_Extension());

Defining new Filters
--------------------

The most common element you will want to add to Twig is filters. A filter is
just a regular PHP function or method that takes the left side of the filter
as first argument and the arguments passed to the filter as extra arguments.

>**CAUTION**
>This section describes the creation of new filters for Twig 0.9.5 and above.

### Function Filters

Let's create a filter, named `rot13`, which returns the
[rot13](http://www.php.net/manual/en/function.str-rot13.php) transformation of
a string:

    [twig]
    {{ "Twig"|rot13 }}

    {# should displays Gjvt #}

A filter is defined as an object of class `Twig_Filter`.

The `Twig_Filter_Function` class can be used to define a filter implemented as
a function:

    [php]
    $filter = new Twig_Filter_Function('str_rot13');

The first argument is the name of the function to call, here `str_rot13`, a
native PHP function.

Registering the filter in an extension means implementing the `getFilters()`
method:

    [php]
    class Project_Twig_Extension extends Twig_Extension
    {
      public function getFilters()
      {
        return array(
          'rot13' => new Twig_Filter_Function('str_rot13'),
        );
      }

      public function getName()
      {
        return 'project';
      }
    }

Parameters passed to the filter are available as extra arguments to the
function call:

    [twig]
    {{ "Twig"|rot13('prefix_') }}

-

    [php]
    function twig_compute_rot13($string, $prefix = '')
    {
      return $prefix.str_rot13($string);
    }

### Class Method Filters

The `Twig_Filter_Function` class can also be used to register static method as
filters:

    [php]
    class Project_Twig_Extension extends Twig_Extension
    {
      public function getFilters()
      {
        return array(
          'rot13' => new Twig_Filter_Function('Project_Twig_Extension::rot13Filter'),
        );
      }

      static public function rot13Filter($string)
      {
        return str_rot13($string);
      }

      public function getName()
      {
        return 'project';
      }
    }

### Object Method Filters

You can also register methods as filters by using the `Twig_Filter_Method`
class:

    [php]
    class Project_Twig_Extension extends Twig_Extension
    {
      public function getFilters()
      {
        return array(
          'rot13' => new Twig_Filter_Method($this, 'rot13Filter'),
        );
      }

      public function rot13Filter($string)
      {
        return str_rot13($string);
      }

      public function getName()
      {
        return 'project';
      }
    }

Using methods for filters is a great way to package your filter without
polluting the global namespace. This also gives the developer more flexibility
at the cost of a small overhead.

### Environment aware Filters

The `Twig_Filter` classes take options as their last argument. For instance, if
you want access to the current environment instance in your filter, set the
`needs_environment` option to `true`:

    [php]
    $filter = new Twig_Filter_Function('str_rot13', array('needs_environment' => true));

Twig will then pass the current environment as the first argument to the
filter call:

    [php]
    function twig_compute_rot13(Twig_Environment $env, $string)
    {
      // get the current charset for instance
      $charset = $env->getCharset();

      return str_rot13($string);
    }

### Automatic Escaping

If automatic escaping is enabled, the main value passed to the filters is
automatically escaped. If your filter acts as an escaper, you will want the
raw variable value. In such a case, set the `is_escaper` option to `true`:

    [php]
    $filter = new Twig_Filter_Function('urlencode', array('is_escaper' => true));

>**NOTE**
>The parameters passed as extra arguments to the filters are not affected by
>the `is_escaper` option and they are always escaped according to the
>automatic escaping rules.

Overriding default Filters
--------------------------

>**CAUTION**
>This section describes how to override default filters for Twig 0.9.5 and
>above.

If some default core filters do not suit your needs, you can easily override
them by creating your own core extension. Of course, you don't need to copy
and paste the whole core extension code of Twig. Instead, you can just extends
it and override the filter(s) by overriding the `getFilters()` method:

    [php]
    class MyCoreExtension extends Twig_Extension_Core
    {
      public function getFilters()
      {
        return array_merge(
          parent::getFilters(),
          array(
            'date' => Twig_Filter_Method($this, 'dateFilter')
          )
        );
      }

      public function dateFilter($timestamp, $format = 'F j, Y H:i')
      {
        return '...'.twig_date_format_filter($timestamp, $format);
      }
    }

Here, we override the `date` filter with a custom one. Using this new core
extension is as simple as registering the `MyCoreExtension` extension by
calling the `addExtension()` method on the environment instance:

    [php]
    $twig = new Twig_Environment($loader, array('debug' => true, 'cache' => false));
    $twig->addExtension(new MyCoreExtension());

But I can already hear some people wondering how it can work as the Core
extension is loaded by default. That's true, but the trick is that both
extensions share the same unique identifier (`core` - defined in the
`getName()` method). By registering an extension with the same name as an
existing one, you have actually overridden the default one, even if it is
already registered:

    [php]
    $twig->addExtension(new Twig_Extension_Core());
    $twig->addExtension(new MyCoreExtension());

Defining new Tags
-----------------

One of the most exiting feature of a template engine like Twig is the
possibility to define new language constructs.

Let's create a simple `set` tag that allows the definition of simple variables
from within a template. The tag can be used like follows:

    [twig]
    {% set name as "value" %}

    {{ name }}

    {# should output value #}

>**NOTE**
>The `set` tag is part of the Core extension and as such is always available.
>The built-in version is slightly more powerful and supports multiple
>assignments by default (cf. the template designers chapter for more
>information).

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
        $this->parser->getStream()->expect(Twig_Token::NAME_TYPE, 'as');
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
   syntax error if not found (the second argument is the expected value of the
   token).

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
