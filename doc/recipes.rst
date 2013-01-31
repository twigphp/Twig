Recipes
=======

Making a Layout conditional
---------------------------

Working with Ajax means that the same content is sometimes displayed as is,
and sometimes decorated with a layout. As Twig layout template names can be
any valid expression, you can pass a variable that evaluates to ``true`` when
the request is made via Ajax and choose the layout accordingly:

.. code-block:: jinja

    {% extends request.ajax ? "base_ajax.html" : "base.html" %}

    {% block content %}
        This is the content to be displayed.
    {% endblock %}

Making an Include dynamic
-------------------------

When including a template, its name does not need to be a string. For
instance, the name can depend on the value of a variable:

.. code-block:: jinja

    {% include var ~ '_foo.html' %}

If ``var`` evaluates to ``index``, the ``index_foo.html`` template will be
rendered.

As a matter of fact, the template name can be any valid expression, such as
the following:

.. code-block:: jinja

    {% include var|default('index') ~ '_foo.html' %}

Overriding a Template that also extends itself
----------------------------------------------

A template can be customized in two different ways:

* *Inheritance*: A template *extends* a parent template and overrides some
  blocks;

* *Replacement*: If you use the filesystem loader, Twig loads the first
  template it finds in a list of configured directories; a template found in a
  directory *replaces* another one from a directory further in the list.

But how do you combine both: *replace* a template that also extends itself
(aka a template in a directory further in the list)?

Let's say that your templates are loaded from both ``.../templates/mysite``
and ``.../templates/default`` in this order. The ``page.twig`` template,
stored in ``.../templates/default`` reads as follows:

.. code-block:: jinja

    {# page.twig #}
    {% extends "layout.twig" %}

    {% block content %}
    {% endblock %}

You can replace this template by putting a file with the same name in
``.../templates/mysite``. And if you want to extend the original template, you
might be tempted to write the following:

.. code-block:: jinja

    {# page.twig in .../templates/mysite #}
    {% extends "page.twig" %} {# from .../templates/default #}

Of course, this will not work as Twig will always load the template from
``.../templates/mysite``.

It turns out it is possible to get this to work, by adding a directory right
at the end of your template directories, which is the parent of all of the
other directories: ``.../templates`` in our case. This has the effect of
making every template file within our system uniquely addressable. Most of the
time you will use the "normal" paths, but in the special case of wanting to
extend a template with an overriding version of itself we can reference its
parent's full, unambiguous template path in the extends tag:

.. code-block:: jinja

    {# page.twig in .../templates/mysite #}
    {% extends "default/page.twig" %} {# from .../templates #}

.. note::

    This recipe was inspired by the following Django wiki page:
    http://code.djangoproject.com/wiki/ExtendingTemplates

Customizing the Syntax
----------------------

Twig allows some syntax customization for the block delimiters. It's not
recommended to use this feature as templates will be tied with your custom
syntax. But for specific projects, it can make sense to change the defaults.

To change the block delimiters, you need to create your own lexer object::

    $twig = new Twig_Environment();

    $lexer = new Twig_Lexer($twig, array(
        'tag_comment'   => array('{#', '#}'),
        'tag_block'     => array('{%', '%}'),
        'tag_variable'  => array('{{', '}}'),
        'interpolation' => array('#{', '}'),
    ));
    $twig->setLexer($lexer);

Here are some configuration example that simulates some other template engines
syntax::

    // Ruby erb syntax
    $lexer = new Twig_Lexer($twig, array(
        'tag_comment'  => array('<%#', '%>'),
        'tag_block'    => array('<%', '%>'),
        'tag_variable' => array('<%=', '%>'),
    ));

    // SGML Comment Syntax
    $lexer = new Twig_Lexer($twig, array(
        'tag_comment'  => array('<!--#', '-->'),
        'tag_block'    => array('<!--', '-->'),
        'tag_variable' => array('${', '}'),
    ));

    // Smarty like
    $lexer = new Twig_Lexer($twig, array(
        'tag_comment'  => array('{*', '*}'),
        'tag_block'    => array('{', '}'),
        'tag_variable' => array('{$', '}'),
    ));

Using dynamic Object Properties
-------------------------------

When Twig encounters a variable like ``article.title``, it tries to find a
``title`` public property in the ``article`` object.

It also works if the property does not exist but is rather defined dynamically
thanks to the magic ``__get()`` method; you just need to also implement the
``__isset()`` magic method like shown in the following snippet of code::

    class Article
    {
        public function __get($name)
        {
            if ('title' == $name) {
                return 'The title';
            }

            // throw some kind of error
        }

        public function __isset($name)
        {
            if ('title' == $name) {
                return true;
            }

            return false;
        }
    }

Accessing the parent Context in Nested Loops
--------------------------------------------

Sometimes, when using nested loops, you need to access the parent context. The
parent context is always accessible via the ``loop.parent`` variable. For
instance, if you have the following template data::

    $data = array(
        'topics' => array(
            'topic1' => array('Message 1 of topic 1', 'Message 2 of topic 1'),
            'topic2' => array('Message 1 of topic 2', 'Message 2 of topic 2'),
        ),
    );

And the following template to display all messages in all topics:

.. code-block:: jinja

    {% for topic, messages in topics %}
        * {{ loop.index }}: {{ topic }}
      {% for message in messages %}
          - {{ loop.parent.loop.index }}.{{ loop.index }}: {{ message }}
      {% endfor %}
    {% endfor %}

The output will be similar to:

.. code-block:: text

    * 1: topic1
      - 1.1: The message 1 of topic 1
      - 1.2: The message 2 of topic 1
    * 2: topic2
      - 2.1: The message 1 of topic 2
      - 2.2: The message 2 of topic 2

In the inner loop, the ``loop.parent`` variable is used to access the outer
context. So, the index of the current ``topic`` defined in the outer for loop
is accessible via the ``loop.parent.loop.index`` variable.

Defining undefined Functions and Filters on the Fly
---------------------------------------------------

When a function (or a filter) is not defined, Twig defaults to throw a
``Twig_Error_Syntax`` exception. However, it can also call a `callback`_ (any
valid PHP callable) which should return a function (or a filter).

For filters, register callbacks with ``registerUndefinedFilterCallback()``.
For functions, use ``registerUndefinedFunctionCallback()``::

    // auto-register all native PHP functions as Twig functions
    // don't try this at home as it's not secure at all!
    $twig->registerUndefinedFunctionCallback(function ($name) {
        if (function_exists($name)) {
            return new Twig_Function_Function($name);
        }

        return false;
    });

If the callable is not able to return a valid function (or filter), it must
return ``false``.

If you register more than one callback, Twig will call them in turn until one
does not return ``false``.

.. tip::

    As the resolution of functions and filters is done during compilation,
    there is no overhead when registering these callbacks.

Validating the Template Syntax
------------------------------

When template code is providing by a third-party (through a web interface for
instance), it might be interesting to validate the template syntax before
saving it. If the template code is stored in a `$template` variable, here is
how you can do it::

    try {
        $twig->parse($twig->tokenize($template));

        // the $template is valid
    } catch (Twig_Error_Syntax $e) {
        // $template contains one or more syntax errors
    }

If you iterate over a set of files, you can pass the filename to the
``tokenize()`` method to get the filename in the exception message::

    foreach ($files as $file) {
        try {
            $twig->parse($twig->tokenize($template, $file));

            // the $template is valid
        } catch (Twig_Error_Syntax $e) {
            // $template contains one or more syntax errors
        }
    }

.. note::

    This method won't catch any sandbox policy violations because the policy
    is enforced during template rendering (as Twig needs the context for some
    checks like allowed methods on objects).

Refreshing modified Templates when APC is enabled and apc.stat = 0
------------------------------------------------------------------

When using APC with ``apc.stat`` set to ``0`` and Twig cache enabled, clearing
the template cache won't update the APC cache. To get around this, one can
extend ``Twig_Environment`` and force the update of the APC cache when Twig
rewrites the cache::

    class Twig_Environment_APC extends Twig_Environment
    {
        protected function writeCacheFile($file, $content)
        {
            parent::writeCacheFile($file, $content);

            // Compile cached file into bytecode cache
            apc_compile_file($file);
        }
    }

Reusing a stateful Node Visitor
-------------------------------

When attaching a visitor to a ``Twig_Environment`` instance, Twig uses it to
visit *all* templates it compiles. If you need to keep some state information
around, you probably want to reset it when visiting a new template.

This can be easily achieved with the following code::

    protected $someTemplateState = array();

    public function enterNode(Twig_NodeInterface $node, Twig_Environment $env)
    {
        if ($node instanceof Twig_Node_Module) {
            // reset the state as we are entering a new template
            $this->someTemplateState = array();
        }

        // ...

        return $node;
    }

Using the Template name to set the default Escaping Strategy
------------------------------------------------------------

.. versionadded:: 1.8
    This recipe requires Twig 1.8 or later.

The ``autoescape`` option determines the default escaping strategy to use when
no escaping is applied on a variable. When Twig is used to mostly generate
HTML files, you can set it to ``html`` and explicitly change it to ``js`` when
you have some dynamic JavaScript files thanks to the ``autoescape`` tag:

.. code-block:: jinja

    {% autoescape 'js' %}
        ... some JS ...
    {% endautoescape %}

But if you have many HTML and JS files, and if your template names follow some
conventions, you can instead determine the default escaping strategy to use
based on the template name. Let's say that your template names always ends
with ``.html`` for HTML files, ``.js`` for JavaScript ones, and ``.css`` for
stylesheets, here is how you can configure Twig::

    class TwigEscapingGuesser
    {
        function guess($filename)
        {
            // get the format
            $format = substr($filename, strrpos($filename, '.') + 1);

            switch ($format) {
                case 'js':
                    return 'js';
                case 'css':
                    return 'css';
                case 'html':
                default:
                    return 'html';
            }
        }
    }

    $loader = new Twig_Loader_Filesystem('/path/to/templates');
    $twig = new Twig_Environment($loader, array(
        'autoescape' => array(new TwigEscapingGuesser(), 'guess'),
    ));

This dynamic strategy does not incur any overhead at runtime as auto-escaping
is done at compilation time.

Using a Database to store Templates
-----------------------------------

If you are developing a CMS, templates are usually stored in a database. This
recipe gives you a simple PDO template loader you can use as a starting point
for your own.

First, let's create a temporary in-memory SQLite3 database to work with::

    $dbh = new PDO('sqlite::memory:');
    $dbh->exec('CREATE TABLE templates (name STRING, source STRING, last_modified INTEGER)');
    $base = '{% block content %}{% endblock %}';
    $index = '
    {% extends "base.twig" %}
    {% block content %}Hello {{ name }}{% endblock %}
    ';
    $now = time();
    $dbh->exec("INSERT INTO templates (name, source, last_modified) VALUES ('base.twig', '$base', $now)");
    $dbh->exec("INSERT INTO templates (name, source, last_modified) VALUES ('index.twig', '$index', $now)");

We have created a simple ``templates`` table that hosts two templates:
``base.twig`` and ``index.twig``.

Now, let's define a loader able to use this database::

    class DatabaseTwigLoader implements Twig_LoaderInterface, Twig_ExistsLoaderInterface
    {
        protected $dbh;

        public function __construct(PDO $dbh)
        {
            $this->dbh = $dbh;
        }

        public function getSource($name)
        {
            if (false === $source = $this->getValue('source', $name)) {
                throw new Twig_Error_Loader(sprintf('Template "%s" does not exist.', $name));
            }

            return $source;
        }

        // Twig_ExistsLoaderInterface as of Twig 1.11
        public function exists($name)
        {
            return $name === $this->getValue('name', $name);
        }

        public function getCacheKey($name)
        {
            return $name;
        }

        public function isFresh($name, $time)
        {
            if (false === $lastModified = $this->getValue('last_modified', $name)) {
                return false;
            }

            return $lastModified <= $time;
        }

        protected function getValue($column, $name)
        {
            $sth = $this->dbh->prepare('SELECT '.$column.' FROM templates WHERE name = :name');
            $sth->execute(array(':name' => (string) $name));

            return $sth->fetchColumn();
        }
    }

Finally, here is an example on how you can use it::

    $loader = new DatabaseTwigLoader($dbh);
    $twig = new Twig_Environment($loader);

    echo $twig->render('index.twig', array('name' => 'Fabien'));

Using different Template Sources
--------------------------------

This recipe is the continuation of the previous one. Even if you store the
contributed templates in a database, you might want to keep the original/base
templates on the filesystem. When templates can be loaded from different
sources, you need to use the ``Twig_Loader_Chain`` loader.

As you can see in the previous recipe, we reference the template in the exact
same way as we would have done it with a regular filesystem loader. This is
the key to be able to mix and match templates coming from the database, the
filesystem, or any other loader for that matter: the template name should be a
logical name, and not the path from the filesystem::

    $loader1 = new DatabaseTwigLoader($dbh);
    $loader2 = new Twig_Loader_Array(array(
        'base.twig' => '{% block content %}{% endblock %}',
    ));
    $loader = new Twig_Loader_Chain(array($loader1, $loader2));

    $twig = new Twig_Environment($loader);

    echo $twig->render('index.twig', array('name' => 'Fabien'));

Now that the ``base.twig`` templates is defined in an array loader, you can
remove it from the database, and everything else will still work as before.

.. _callback: http://www.php.net/manual/en/function.is-callable.php
