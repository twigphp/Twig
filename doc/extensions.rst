Creating a Twig Extension
=========================

The main motivation for writing an extension is to move often used code into a
reusable class like adding support for internationalization. An extension can
define tags, filters, tests, operators, global variables, functions, and node
visitors.

Creating an extension also makes for a better separation of code that is
executed at compilation time and code needed at runtime. As such, it makes
your code faster.

Most of the time, it is useful to create a single extension for your project,
to host all the specific tags and filters you want to add to Twig.

.. note::

    Before writing your own extensions, have a look at the Twig official
    extension repository: http://github.com/fabpot/Twig-extensions.

An extension is a class that implements the following interface::

    interface Twig_ExtensionInterface
    {
        /**
         * Initializes the runtime environment.
         *
         * This is where you can load some file that contains filter functions for instance.
         *
         * @param Twig_Environment $environment The current Twig_Environment instance
         */
        public function initRuntime(Twig_Environment $environment);

        /**
         * Returns the token parser instances to add to the existing list.
         *
         * @return array An array of Twig_TokenParserInterface or Twig_TokenParserBrokerInterface instances
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
         * Returns a list of operators to add to the existing list.
         *
         * @return array An array of operators
         */
        public function getOperators();

        /**
         * Returns a list of global functions to add to the existing list.
         *
         * @return array An array of global functions
         */
        public function getGlobals();

        /**
         * Returns the name of the extension.
         *
         * @return string The extension name
         */
        public function getName();
    }

To keep your extension class clean and lean, it can inherit from the built-in
``Twig_Extension`` class instead of implementing the whole interface. That
way, you just need to implement the ``getName()`` method as the
``Twig_Extension`` provides empty implementations for all other methods.

The ``getName()`` method must return a unique identifier for your extension.

Now, with this information in mind, let's create the most basic extension
possible::

    class Project_Twig_Extension extends Twig_Extension
    {
        public function getName()
        {
            return 'project';
        }
    }

.. note::

    Of course, this extension does nothing for now. We will customize it in
    the next sections.

Twig does not care where you save your extension on the filesystem, as all
extensions must be registered explicitly to be available in your templates.

You can register an extension by using the ``addExtension()`` method on your
main ``Environment`` object::

    $twig = new Twig_Environment($loader);
    $twig->addExtension(new Project_Twig_Extension());

Of course, you need to first load the extension file by either using
``require_once()`` or by using an autoloader (see `spl_autoload_register()`_).

.. tip::

    The bundled extensions are great examples of how extensions work.

Globals and Functions
---------------------

Global variables and functions can be registered in an extensions via the
``getGlobals()`` method::

    class Project_Twig_Extension extends Twig_Extension
    {
        public function getGlobals()
        {
            return array(
                'text' => new Text(),
                'lipsum' => new Twig_Function(new Text(), 'getLipsum'),
            );
        }

        // ...
    }

Filters
-------

To add a filter to an extension, you need to override the ``getFilters()``
method. This method must return an array of filters to add to the Twig
environment::

    class Project_Twig_Extension extends Twig_Extension
    {
        public function getFilters()
        {
            return array(
                'rot13' => new Twig_Filter_Function('str_rot13'),
            );
        }

        // ...
    }

As you can see in the above code, the ``getFilters()`` method returns an array
where keys are the name of the filters (``rot13``) and the values the
definition of the filter (``new Twig_Filter_Function('str_rot13')``).

As seen in the previous chapter, you can also define filters as static methods
on the extension class::

$twig->addFilter('rot13', new Twig_Filter_Function('Project_Twig_Extension::rot13Filter'));

You can also use ``Twig_Filter_Method`` instead of ``Twig_Filter_Function``
when defining a filter to use a method::

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

        // ...
    }

The first argument of the ``Twig_Filter_Method`` constructor is always
``$this``, the current extension object. The second one is the name of the
method to call.

Using methods for filters is a great way to package your filter without
polluting the global namespace. This also gives the developer more flexibility
at the cost of a small overhead.

Overriding default Filters
~~~~~~~~~~~~~~~~~~~~~~~~~~

If some default core filters do not suit your needs, you can easily override
them by creating your own core extension. Of course, you don't need to copy
and paste the whole core extension code of Twig. Instead, you can just extends
it and override the filter(s) you want by overriding the ``getFilters()``
method::

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

        // ...
    }

Here, we override the ``date`` filter with a custom one. Using this new core
extension is as simple as registering the ``MyCoreExtension`` extension by
calling the ``addExtension()`` method on the environment instance::

    $twig = new Twig_Environment($loader);
    $twig->addExtension(new MyCoreExtension());

But I can already hear some people wondering how it can work as the Core
extension is loaded by default. That's true, but the trick is that both
extensions share the same unique identifier (``core`` - defined in the
``getName()`` method). By registering an extension with the same name as an
existing one, you have actually overridden the default one, even if it is
already registered::

    $twig->addExtension(new Twig_Extension_Core());
    $twig->addExtension(new MyCoreExtension());

Tags
----

Adding a tag in an extension can be done by overriding the
``getTokenParsers()`` method. This method must return an array of tags to add
to the Twig environment::

    class Project_Twig_Extension extends Twig_Extension
    {
        public function getTokenParsers()
        {
            return array(new Project_Set_TokenParser());
        }

        // ...
    }

In the above code, we have added a single new tag, defined by the
``Project_Set_TokenParser`` class. The ``Project_Set_TokenParser`` class is
responsible for parsing the tag and compiling it to PHP.

Operators
---------

The ``getOperators()`` methods allows to add new operators. Here is how to add
``!``, ``||``, and ``&&`` operators::

    class Project_Twig_Extension extends Twig_Extension
    {
        public function getOperators()
        {
            return array(
                array(
                    '!' => array('precedence' => 50, 'class' => 'Twig_Node_Expression_Unary_Not'),
                ),
                array(
                    '||' => array('precedence' => 10, 'class' => 'Twig_Node_Expression_Binary_Or', 'associativity' => Twig_ExpressionParser::OPERATOR_LEFT),
                    '&&' => array('precedence' => 15, 'class' => 'Twig_Node_Expression_Binary_And', 'associativity' => Twig_ExpressionParser::OPERATOR_LEFT),
                ),
            );
        }

        // ...
    }

Tests
-----

The ``getTests()`` methods allows to add new test functions::

    class Project_Twig_Extension extends Twig_Extension
    {
        public function getTests()
        {
            return array(
                'even' => new Twig_Test_Function('twig_test_even'),
            );
        }

        // ...
    }

.. _`spl_autoload_register()`: http://www.php.net/spl_autoload_register
