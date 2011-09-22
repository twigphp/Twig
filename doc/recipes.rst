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
  template if finds in a list of configured directories; a template found in a
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
        'tag_comment'  => array('{#', '#}'),
        'tag_block'    => array('{%', '%}'),
        'tag_variable' => array('{{', '}}'),
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
            if ('title' == $name)
            {
                return 'The title';
            }

            // throw some kind of error
        }

        public function __isset($name)
        {
            if ('title' == $name)
            {
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

.. _callback: http://www.php.net/manual/en/function.is-callable.php
