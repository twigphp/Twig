``extends``
===========

The ``extends`` tag can be used to extend a template from another one.

.. note::

    Like PHP, Twig does not support multiple inheritance. So you can only have
    one extends tag called per rendering. However, Twig supports horizontal
    :doc:`reuse<use>`.

Let's define a base template, ``base.html``, which defines a simple HTML
skeleton document:

.. code-block:: html+jinja

    <!DOCTYPE html>
    <html>
        <head>
            {% block head %}
                <link rel="stylesheet" href="style.css" />
                <title>{% block title %}{% endblock %} - My Webpage</title>
            {% endblock %}
        </head>
        <body>
            <div id="content">{% block content %}{% endblock %}</div>
            <div id="footer">
                {% block footer %}
                    &copy; Copyright 2011 by <a href="http://domain.invalid/">you</a>.
                {% endblock %}
            </div>
        </body>
    </html>

In this example, the :doc:`{% block %}<block>` tags define four blocks
that child templates can fill in. All the ``block`` tag does is to tell the
template engine that a child template may override those portions of the
template.

Child Template
--------------

A child template might look like this:

.. code-block:: jinja

    {% extends "base.html" %}

    {% block title %}Index{% endblock %}
    {% block head %}
        {{ parent() }}
        <style type="text/css">
            .important { color: #336699; }
        </style>
    {% endblock %}
    {% block content %}
        <h1>Index</h1>
        <p class="important">
            Welcome on my awesome homepage.
        </p>
    {% endblock %}

The ``{% extends %}`` tag is the key here. It tells the template engine that
this template "extends" another template. When the template system evaluates
this template, first it locates the parent. The extends tag should be the
first tag in the template.

Note that since the child template doesn't define the ``footer`` block, the
value from the parent template is used instead.

You can't define multiple ``{% block %}`` tags with the same name in the same
template. This limitation exists because a block tag works in "both"
directions. That is, a block tag doesn't just provide a hole to fill - it also
defines the content that fills the hole in the *parent*. If there were two
similarly-named ``{% block %}`` tags in a template, that template's parent
wouldn't know which one of the blocks' content to use.

If you want to print a block multiple times you can however use the
``block`` function:

.. code-block:: jinja

    <title>{% block title %}{% endblock %}</title>
    <h1>{{ block('title') }}</h1>
    {% block body %}{% endblock %}

Parent Blocks
-------------

It's possible to render the contents of the parent block by using the
:doc:`parent<../functions/parent>` function. This gives back the results of
the parent block:

.. code-block:: jinja

    {% block sidebar %}
        <h3>Table Of Contents</h3>
        ...
        {{ parent() }}
    {% endblock %}

Named Block End-Tags
--------------------

Twig allows you to put the name of the block after the end tag for better
readability:

.. code-block:: jinja

    {% block sidebar %}
        {% block inner_sidebar %}
            ...
        {% endblock inner_sidebar %}
    {% endblock sidebar %}

Of course, the name after the ``endblock`` word must match the block name.

Block Nesting and Scope
-----------------------

Blocks can be nested for more complex layouts. Per default, blocks have access
to variables from outer scopes:

.. code-block:: jinja

    {% for item in seq %}
        <li>{% block loop_item %}{{ item }}{% endblock %}</li>
    {% endfor %}

Block Shortcuts
---------------

For blocks with few content, it's possible to use a shortcut syntax. The
following constructs do the same:

.. code-block:: jinja

    {% block title %}
        {{ page_title|title }}
    {% endblock %}

.. code-block:: jinja

    {% block title page_title|title %}

Dynamic Inheritance
-------------------

Twig supports dynamic inheritance by using a variable as the base template:

.. code-block:: jinja

    {% extends some_var %}

If the variable evaluates to a ``Twig_Template`` object, Twig will use it as
the parent template::

    // {% extends layout %}

    $layout = $twig->loadTemplate('some_layout_template.twig');

    $twig->display('template.twig', array('layout' => $layout));

.. versionadded:: 1.2
    The possibility to pass an array of templates has been added in Twig 1.2.

You can also provide a list of templates that are checked for existence. The
first template that exists will be used as a parent:

.. code-block:: jinja

    {% extends ['layout.html', 'base_layout.html'] %}

Conditional Inheritance
-----------------------

As the template name for the parent can be any valid Twig expression, it's
possible to make the inheritance mechanism conditional:

.. code-block:: jinja

    {% extends standalone ? "minimum.html" : "base.html" %}

In this example, the template will extend the "minimum.html" layout template
if the ``standalone`` variable evaluates to ``true``, and "base.html"
otherwise.

.. seealso:: :doc:`block<../functions/block>`, :doc:`block<../tags/block>`, :doc:`parent<../functions/parent>`, :doc:`use<../tags/use>`
