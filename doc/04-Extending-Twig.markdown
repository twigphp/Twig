Extending Twig
==============

Twig supports extensions that can add extra tags, filters, or even extend the
parser itself with node visitor classes. The main motivation for writing
an extension is to move often used code into a reusable class like adding
support for internationalization.

Most of the time, it is useful to create a single extension for your project,
to host all the specific tags and filters you want to add to Twig.

>**NOTE**
>Before writing your own extensions, have a look at the Twig official extension
>repository: http://github.com/fabpot/Twig-extensions.

Extending without an Extension (new in Twig 0.9.7)
--------------------------------------------------

If you just need to register a small amount of tags and/or filters, you can
register them without creating an extension:

    [php]
    $twig = new Twig_Environment($loader);
    $twig->addTokenParser(new CustomTokenParser());
    $twig->addFilter('upper', new Twig_Filter_Function('strtoupper'));

Anatomy of an Extension
-----------------------

An extension is a class that implements the following interface:

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
       * Returns the node visitor instances to add to the existing list.
       *
       * @return array An array of Twig_NodeVisitorInterface instances
       */
      public function getNodeVisitors();

      /**
       * Returns a list of filters to add to the existing list.
       *
       * @return array An array of filters
       */
      public function getFilters();

      /**
       * Returns a list of tests to add to the existing list.
       *
       * @return array An array of tests
       */
      public function getTests();

      /**
       * Returns the name of the extension.
       *
       * @return string The extension name
       */
      public function getName();
    }

To keep your extension class clean and lean, it can inherit from the built-in
`Twig_Extension` class instead of implementing the whole interface. That
way, you just need to implement the `getName()` method as the
`Twig_Extension` provides empty implementations for all other methods.

The `getName()` method must return a unique identifier for your extension.

Now, with this information in mind, let's create the most basic extension
possible:

    [php]
    class Project_Twig_Extension extends Twig_Extension
    {
      public function getName()
      {
        return 'project';
      }
    }

>**NOTE**
>Of course, this extension does nothing for now. We will add tags and filters
>in the coming sections.

Twig does not care where you save your extension on the filesystem, as all
extensions must be registered explicitly to be available in your templates.

You can register an extension by using the `addExtension()` method on your
main `Environment` object:

    [php]
    $twig = new Twig_Environment($loader);
    $twig->addExtension(new Project_Twig_Extension());

Of course, you need to first load the extension file by either using
`require_once()` or by using an autoloader (see
[`spl_autoload_register()`](http://www.php.net/spl_autoload_register)).

>**TIP**
>The bundled extensions are great examples of how extensions work.

Defining new Filters
--------------------

>**CAUTION**
>This section describes the creation of new filters for Twig 0.9.5 and above.

The most common element you will want to add to Twig is filters. A filter is
just a regular PHP function or a method that takes the left side of the filter
(before the pipe `|`) as first argument and the extra arguments passed to the
filter (within parentheses `()`) as extra arguments.

For instance, let's say you have the following code in a template:

    [twig]
    {{ 'TWIG'|lower }}

When compiling this template to PHP, Twig will first look for the PHP function
associated with the `lower` filter. The `lower` filter is a built-in Twig
filter, and it is simply mapped to the PHP `strtolower()` function. After
compilation, the generated PHP code is roughly equivalent to:

    [php]
    <?php echo strtolower('TWIG') ?>

As you can see, the `'TWIG'` string is passed as a first argument to the
PHP function.

A filter can also take extra arguments like in the following example:

    [twig]
    {{ now|date('d/m/Y') }}

In this case, the extra arguments are passed to the function after the main
argument, and the compiled code is equivalent to:

    [php]
    <?php echo twig_date_format_filter($now, 'd/m/Y') ?>

### Function Filters

Let's see how to create a new filter.

In this section, we will create a `rot13` filter, which should return the
[rot13](http://www.php.net/manual/en/function.str-rot13.php) transformation of
a string. Here is an example of its usage and the expected output:

    [twig]
    {{ "Twig"|rot13 }}

    {# should displays Gjvt #}

Adding a filter is as simple as calling the `addFilter` method of the
`Twig_Environment` instance (new in Twig 0.9.7):

    [php]
    $twig = new Twig_Environment($loader);
    $twig->addFilter('upper', new Twig_Filter_Function('strtoupper'));

To add a filter to an extension, you need to override the `getFilters()`
method. This method must return an array of filters to add to the Twig
environment:

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

As you can see in the above code, the `getFilters()` method returns an array
where keys are the name of the filters (`rot13`) and the values the definition
of the filter (`new Twig_Filter_Function('str_rot13')`).

The definition of a filter is always an object. For this first example, we
have defined a filter as an object of the `Twig_Filter_Function` class.

The `Twig_Filter_Function` class is to be used when you need to define a
filter implemented as a function. The first argument passed to the
`Twig_Filter_Function` constructor is the name of the function to call, here
`str_rot13`, a native PHP function.

Let's say I now want to be able to add a prefix before the converted string:

    [twig]
    {{ "Twig"|rot13('prefix_') }}

    {# should displays prefix_Gjvt #}

As the PHP `str_rot13()` function does not support this requirement, let's
create a new PHP function:

    [php]
    function project_compute_rot13($string, $prefix = '')
    {
      return $prefix.str_rot13($string);
    }

As you can see, the `prefix` argument of the filter is passed as an extra
argument to the `project_compute_rot13()` function.

>**NOTE**
>This function can declared anywhere by it is a good idea to define it in the
>same file as the extension class.

The new extension code looks very similar to the previous one:

    [php]
    class Project_Twig_Extension extends Twig_Extension
    {
      public function getFilters()
      {
        return array(
          'rot13' => new Twig_Filter_Function('project_compute_rot13'),
        );
      }

      public function getName()
      {
        return 'project';
      }
    }

### Class Method Filters

Instead of creating a function to define a filter as we have done before, you
can also create a static method in a class for better encapsulation.

The `Twig_Filter_Function` class can also be used to register such static
methods as filters:

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

Defining static methods is one step towards better encapsulation, but defining
filters as methods of your extension class is probably the best solution.

This is possible by using `Twig_Filter_Method` instead of
`Twig_Filter_Function` when defining a filter:

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

The first argument of the `Twig_Filter_Method` constructor is always `$this`,
the current extension object. The second one is the name of the method to
call.

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

If automatic escaping is enabled, the output of the filter may be escaped before
printing. If your filter acts as an escaper (or explicitly outputs html or
javascript code), you will want the raw output to be printed. In such a case,
set the `is_safe` option:

    [php]
    $filter = new Twig_Filter_Function('nl2br', array('is_safe' => array('html')));

Some advanced filters may have to work on already escaped or safe values. In
such a case, set the `pre_escape` option:

    [php]
    $filter = new Twig_Filter_Function('somefilter', array('pre_escape' => 'html', 'is_safe' => array('html')));

Overriding default Filters
--------------------------

>**CAUTION**
>This section describes how to override default filters for Twig 0.9.5 and
>above.

If some default core filters do not suit your needs, you can easily override
them by creating your own core extension. Of course, you don't need to copy
and paste the whole core extension code of Twig. Instead, you can just extends
it and override the filter(s) you want by overriding the `getFilters()`
method:

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
    $twig = new Twig_Environment($loader);
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

One of the most exciting feature of a template engine like Twig is the
possibility to define new language constructs.

Let's create a simple `set` tag that allows the definition of simple variables
from within a template. The tag can be used like follows:

    [twig]
    {% set name = "value" %}

    {{ name }}

    {# should output value #}

>**NOTE**
>The `set` tag is part of the Core extension and as such is always available.
>The built-in version is slightly more powerful and supports multiple
>assignments by default (cf. the template designers chapter for more
>information).

Three steps are needed to define a new tag:

  * Defining a Token Parser class (responsible for parsing the template code)

  * Defining a Node class (responsible for converting the parsed code to PHP)

  * Registering the tag in an extension

### Registering a new tag

Adding a tag in an extension can be done by overriding the `getTokenParsers()`
method. This method must return an array of tags to add to the Twig
environment:

    [php]
    class Project_Twig_Extension extends Twig_Extension
    {
      public function getTokenParsers()
      {
        return array(new Project_Set_TokenParser());
      }

      // ...
    }

In the above code, we have added a single new tag, defined by the
`Project_Set_TokenParser` class. The `Project_Set_TokenParser` class is
responsible for parsing the tag and compiling it to PHP.

### Defining a Token Parser

Now, let's see the actual code of this class:

    [php]
    class Project_Set_TokenParser extends Twig_TokenParser
    {
      public function parse(Twig_Token $token)
      {
        $lineno = $token->getLine();
        $name = $this->parser->getStream()->expect(Twig_Token::NAME_TYPE)->getValue();
        $this->parser->getStream()->expect(Twig_Token::OPERATOR_TYPE, '=');
        $value = $this->parser->getExpressionParser()->parseExpression();

        $this->parser->getStream()->expect(Twig_Token::BLOCK_END_TYPE);

        return new Project_Set_Node($name, $value, $lineno, $this->getTag());
      }

      public function getTag()
      {
        return 'set';
      }
    }

The `getTag()` method must return the tag we want to parse, here `set`.

The `parse()` method is invoked whenever the parser encounters a `set` tag. It
should return a `Twig_Node` instance that represents the node (the
`Project_Set_Node` calls creating is explained in the next section).

The parsing process is simplified thanks to a bunch of methods you can call
from the token stream (`$this->parser->getStream()`):

 * `test()`: Tests the type and optionally the value of the next token and
   returns it.

 * `expect()`: Expects a token and returns it (like `test()`) or throw a
   syntax error if not found (the second argument is the expected value of the
   token).

 * `look()`: Looks a the next token. This is how you can have a look at the
   next token without consuming it (after you are done with `look()`, you must
   use `rewind()`).

Parsing expressions is done by calling the `parseExpression()` like we did for
the `set` tag.

>**TIP**
>Reading the existing `TokenParser` classes is the best way to learn all the
>nitty-gritty details of the parsing process.

### Defining a Node

The `Project_Set_Node` class itself is rather simple:

    [php]
    class Project_Set_Node extends Twig_Node
    {
      public function __construct($name, Twig_Node_Expression $value, $lineno)
      {
        parent::__construct(array('value' => $value), array('name' => $name), $lineno);
      }

      public function compile($compiler)
      {
        $compiler
          ->addDebugInfo($this)
          ->write('$context[\''.$this->getAttribute('name').'\'] = ')
          ->subcompile($this->getNode('value'))
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

 * `addDebugInfo()`: Adds the line of the original template file related to
   the current node as a comment.

 * `indent()`: Indents the generated code (see `Twig_Node_Block` for a usage
   example).

 * `outdent()`: Outdents the generated code (see `Twig_Node_Block` for a usage
   example).

Creating a Node Visitor
-----------------------

To be written...
