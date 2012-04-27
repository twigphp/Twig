Twig for Developers
===================

This chapter describes the API to Twig and not the template language. It will
be most useful as reference to those implementing the template interface to
the application and not those who are creating Twig templates.

Basics
------

Twig uses a central object called the **environment** (of class
``Twig_Environment``). Instances of this class are used to store the
configuration and extensions, and are used to load templates from the file
system or other locations.

Most applications will create one ``Twig_Environment`` object on application
initialization and use that to load templates. In some cases it's however
useful to have multiple environments side by side, if different configurations
are in use.

The simplest way to configure Twig to load templates for your application
looks roughly like this::

    require_once '/path/to/lib/Twig/Autoloader.php';
    Twig_Autoloader::register();

    $loader = new Twig_Loader_Filesystem('/path/to/templates');
    $twig = new Twig_Environment($loader, array(
        'cache' => '/path/to/compilation_cache',
    ));

This will create a template environment with the default settings and a loader
that looks up the templates in the ``/path/to/templates/`` folder. Different
loaders are available and you can also write your own if you want to load
templates from a database or other resources.

.. note::

    Notice that the second argument of the environment is an array of options.
    The ``cache`` option is a compilation cache directory, where Twig caches
    the compiled templates to avoid the parsing phase for sub-sequent
    requests. It is very different from the cache you might want to add for
    the evaluated templates. For such a need, you can use any available PHP
    cache library.

To load a template from this environment you just have to call the
``loadTemplate()`` method which then returns a ``Twig_Template`` instance::

    $template = $twig->loadTemplate('index.html');

To render the template with some variables, call the ``render()`` method::

    echo $template->render(array('the' => 'variables', 'go' => 'here'));

.. note::

    The ``display()`` method is a shortcut to output the template directly.

You can also load and render the template in one fell swoop::

    echo $twig->render('index.html', array('the' => 'variables', 'go' => 'here'));

.. _environment_options:

Environment Options
-------------------

When creating a new ``Twig_Environment`` instance, you can pass an array of
options as the constructor second argument::

    $twig = new Twig_Environment($loader, array('debug' => true));

The following options are available:

* ``debug``: When set to ``true``, the generated templates have a
  ``__toString()`` method that you can use to display the generated nodes
  (default to ``false``).

* ``charset``: The charset used by the templates (default to ``utf-8``).

* ``base_template_class``: The base template class to use for generated
  templates (default to ``Twig_Template``).

* ``cache``: An absolute path where to store the compiled templates, or
  ``false`` to disable caching (which is the default).

* ``auto_reload``: When developing with Twig, it's useful to recompile the
  template whenever the source code changes. If you don't provide a value for
  the ``auto_reload`` option, it will be determined automatically based on the
  ``debug`` value.

* ``strict_variables``: If set to ``false``, Twig will silently ignore invalid
  variables (variables and or attributes/methods that do not exist) and
  replace them with a ``null`` value. When set to ``true``, Twig throws an
  exception instead (default to ``false``).

* ``autoescape``: If set to ``true``, auto-escaping will be enabled by default
  for all templates (default to ``true``). As of Twig 1.8, you can set the
  escaping strategy to use (``html``, ``js``, ``false`` to disable, or a PHP
  callback that takes the template "filename" and must return the escaping
  strategy to use).

* ``optimizations``: A flag that indicates which optimizations to apply
  (default to ``-1`` -- all optimizations are enabled; set it to ``0`` to
  disable).

Loaders
-------

Loaders are responsible for loading templates from a resource such as the file
system.

Compilation Cache
~~~~~~~~~~~~~~~~~

All template loaders can cache the compiled templates on the filesystem for
future reuse. It speeds up Twig a lot as templates are only compiled once; and
the performance boost is even larger if you use a PHP accelerator such as APC.
See the ``cache`` and ``auto_reload`` options of ``Twig_Environment`` above
for more information.

Built-in Loaders
~~~~~~~~~~~~~~~~

Here is a list of the built-in loaders Twig provides:

* ``Twig_Loader_Filesystem``: Loads templates from the file system. This
  loader can find templates in folders on the file system and is the preferred
  way to load them::

        $loader = new Twig_Loader_Filesystem($templateDir);

  It can also look for templates in an array of directories::

        $loader = new Twig_Loader_Filesystem(array($templateDir1, $templateDir2));

  With such a configuration, Twig will first look for templates in
  ``$templateDir1`` and if they do not exist, it will fallback to look for
  them in the ``$templateDir2``.

* ``Twig_Loader_String``: Loads templates from a string. It's a dummy loader
  as you pass it the source code directly::

        $loader = new Twig_Loader_String();

* ``Twig_Loader_Array``: Loads a template from a PHP array. It's passed an
  array of strings bound to template names. This loader is useful for unit
  testing::

        $loader = new Twig_Loader_Array($templates);

.. tip::

    When using the ``Array`` or ``String`` loaders with a cache mechanism, you
    should know that a new cache key is generated each time a template content
    "changes" (the cache key being the source code of the template). If you
    don't want to see your cache grows out of control, you need to take care
    of clearing the old cache file by yourself.

Create your own Loader
~~~~~~~~~~~~~~~~~~~~~~

All loaders implement the ``Twig_LoaderInterface``::

    interface Twig_LoaderInterface
    {
        /**
         * Gets the source code of a template, given its name.
         *
         * @param  string $name string The name of the template to load
         *
         * @return string The template source code
         */
        function getSource($name);

        /**
         * Gets the cache key to use for the cache for a given template name.
         *
         * @param  string $name string The name of the template to load
         *
         * @return string The cache key
         */
        function getCacheKey($name);

        /**
         * Returns true if the template is still fresh.
         *
         * @param string    $name The template name
         * @param timestamp $time The last modification time of the cached template
         */
        function isFresh($name, $time);
    }

As an example, here is how the built-in ``Twig_Loader_String`` reads::

    class Twig_Loader_String implements Twig_LoaderInterface
    {
        public function getSource($name)
        {
          return $name;
        }

        public function getCacheKey($name)
        {
          return $name;
        }

        public function isFresh($name, $time)
        {
          return false;
        }
    }

The ``isFresh()`` method must return ``true`` if the current cached template
is still fresh, given the last modification time, or ``false`` otherwise.

Using Extensions
----------------

Twig extensions are packages that add new features to Twig. Using an
extension is as simple as using the ``addExtension()`` method::

    $twig->addExtension(new Twig_Extension_Sandbox());

Twig comes bundled with the following extensions:

* *Twig_Extension_Core*: Defines all the core features of Twig.

* *Twig_Extension_Escaper*: Adds automatic output-escaping and the possibility
  to escape/unescape blocks of code.

* *Twig_Extension_Sandbox*: Adds a sandbox mode to the default Twig
  environment, making it safe to evaluated untrusted code.

* *Twig_Extension_Optimizer*: Optimizers the node tree before compilation.

The core, escaper, and optimizer extensions do not need to be added to the
Twig environment, as they are registered by default. You can disable an
already registered extension::

    $twig->removeExtension('escaper');

Built-in Extensions
-------------------

This section describes the features added by the built-in extensions.

.. tip::

    Read the chapter about extending Twig to learn how to create your own
    extensions.

Core Extension
~~~~~~~~~~~~~~

The ``core`` extension defines all the core features of Twig:

* Tags:

  * ``for``
  * ``if``
  * ``extends``
  * ``include``
  * ``block``
  * ``filter``
  * ``macro``
  * ``import``
  * ``from``
  * ``set``
  * ``spaceless``

* Filters:

  * ``date``
  * ``format``
  * ``replace``
  * ``url_encode``
  * ``json_encode``
  * ``title``
  * ``capitalize``
  * ``upper``
  * ``lower``
  * ``striptags``
  * ``join``
  * ``reverse``
  * ``length``
  * ``sort``
  * ``merge``
  * ``default``
  * ``keys``
  * ``escape``
  * ``e``

* Functions:

  * ``range``
  * ``constant``
  * ``cycle``
  * ``parent``
  * ``block``

* Tests:

  * ``even``
  * ``odd``
  * ``defined``
  * ``sameas``
  * ``null``
  * ``divisibleby``
  * ``constant``
  * ``empty``

Escaper Extension
~~~~~~~~~~~~~~~~~

The ``escaper`` extension adds automatic output escaping to Twig. It defines a
tag, ``autoescape``, and a filter, ``raw``.

When creating the escaper extension, you can switch on or off the global
output escaping strategy::

    $escaper = new Twig_Extension_Escaper(true);
    $twig->addExtension($escaper);

If set to ``true``, all variables in templates are escaped (using the ``html``
escaping strategy), except those using the ``raw`` filter:

.. code-block:: jinja

    {{ article.to_html|raw }}

You can also change the escaping mode locally by using the ``autoescape`` tag
(see the :doc:`autoescape<tags/autoescape>` doc for the syntax used before
Twig 1.8):

.. code-block:: jinja

    {% autoescape 'html' %}
        {{ var }}
        {{ var|raw }}      {# var won't be escaped #}
        {{ var|escape }}   {# var won't be double-escaped #}
    {% endautoescape %}

.. warning::

    The ``autoescape`` tag has no effect on included files.

The escaping rules are implemented as follows:

* Literals (integers, booleans, arrays, ...) used in the template directly as
  variables or filter arguments are never automatically escaped:

  .. code-block:: jinja

        {{ "Twig<br />" }} {# won't be escaped #}

        {% set text = "Twig<br />" %}
        {{ text }} {# will be escaped #}

* Expressions which the result is always a literal or a variable marked safe
  are never automatically escaped:

  .. code-block:: jinja

        {{ foo ? "Twig<br />" : "<br />Twig" }} {# won't be escaped #}

        {% set text = "Twig<br />" %}
        {{ foo ? text : "<br />Twig" }} {# will be escaped #}

        {% set text = "Twig<br />" %}
        {{ foo ? text|raw : "<br />Twig" }} {# won't be escaped #}

        {% set text = "Twig<br />" %}
        {{ foo ? text|escape : "<br />Twig" }} {# the result of the expression won't be escaped #}

* Escaping is applied before printing, after any other filter is applied:

  .. code-block:: jinja

        {{ var|upper }} {# is equivalent to {{ var|upper|escape }} #}

* The `raw` filter should only be used at the end of the filter chain:

  .. code-block:: jinja

        {{ var|raw|upper }} {# will be escaped #}

        {{ var|upper|raw }} {# won't be escaped #}

* Automatic escaping is not applied if the last filter in the chain is marked
  safe for the current context (e.g. ``html`` or ``js``). ``escaper`` and
  ``escaper('html')`` are marked safe for html, ``escaper('js')`` is marked
  safe for javascript, ``raw`` is marked safe for everything.

  .. code-block:: jinja

        {% autoescape true js %}
        {{ var|escape('html') }} {# will be escaped for html and javascript #}
        {{ var }} {# will be escaped for javascript #}
        {{ var|escape('js') }} {# won't be double-escaped #}
        {% endautoescape %}

.. note::

    Note that autoescaping has some limitations as escaping is applied on
    expressions after evaluation. For instance, when working with
    concatenation, ``{{ foo|raw ~ bar }}`` won't give the expected result as
    escaping is applied on the result of the concatenation, not on the
    individual variables (so, the ``raw`` filter won't have any effect here).

Sandbox Extension
~~~~~~~~~~~~~~~~~

The ``sandbox`` extension can be used to evaluate untrusted code. Access to
unsafe attributes and methods is prohibited. The sandbox security is managed
by a policy instance. By default, Twig comes with one policy class:
``Twig_Sandbox_SecurityPolicy``. This class allows you to white-list some
tags, filters, properties, and methods::

    $tags = array('if');
    $filters = array('upper');
    $methods = array(
        'Article' => array('getTitle', 'getBody'),
    );
    $properties = array(
        'Article' => array('title', 'body'),
    );
    $functions = array('range');
    $policy = new Twig_Sandbox_SecurityPolicy($tags, $filters, $methods, $properties, $functions);

With the previous configuration, the security policy will only allow usage of
the ``if`` tag, and the ``upper`` filter. Moreover, the templates will only be
able to call the ``getTitle()`` and ``getBody()`` methods on ``Article``
objects, and the ``title`` and ``body`` public properties. Everything else
won't be allowed and will generate a ``Twig_Sandbox_SecurityError`` exception.

The policy object is the first argument of the sandbox constructor::

    $sandbox = new Twig_Extension_Sandbox($policy);
    $twig->addExtension($sandbox);

By default, the sandbox mode is disabled and should be enabled when including
untrusted template code by using the ``sandbox`` tag:

.. code-block:: jinja

    {% sandbox %}
        {% include 'user.html' %}
    {% endsandbox %}

You can sandbox all templates by passing ``true`` as the second argument of
the extension constructor::

    $sandbox = new Twig_Extension_Sandbox($policy, true);

Optimizer Extension
~~~~~~~~~~~~~~~~~~~

The ``optimizer`` extension optimizes the node tree before compilation::

    $twig->addExtension(new Twig_Extension_Optimizer());

By default, all optimizations are turned on. You can select the ones you want
to enable by passing them to the constructor::

    $optimizer = new Twig_Extension_Optimizer(Twig_NodeVisitor_Optimizer::OPTIMIZE_FOR);

    $twig->addExtension($optimizer);

Exceptions
----------

Twig can throw exceptions:

* ``Twig_Error``: The base exception for all errors.

* ``Twig_Error_Syntax``: Thrown to tell the user that there is a problem with
  the template syntax.

* ``Twig_Error_Runtime``: Thrown when an error occurs at runtime (when a filter
  does not exist for instance).

* ``Twig_Error_Loader``: Thrown when an error occurs during template loading.

* ``Twig_Sandbox_SecurityError``: Thrown when an unallowed tag, filter, or
  method is called in a sandboxed template.
