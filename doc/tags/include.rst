``include``
===========

The ``include`` statement includes a template and return the rendered content
of that file into the current namespace:

.. code-block:: jinja

    {% include 'header.html' %}
        Body
    {% include 'footer.html' %}

Included templates have access to the variables of the active context.

If you are using the filesystem loader, the templates are looked for in the
paths defined by it.

You can add additional variables by passing them after the ``with`` keyword:

.. code-block:: jinja

    {# the foo template will have access to the variables from the current context and the foo one #}
    {% include 'foo' with {'foo': 'bar'} %}

    {% set vars = {'foo': 'bar'} %}
    {% include 'foo' with vars %}

You can disable access to the context by appending the ``only`` keyword:

.. code-block:: jinja

    {# only the foo variable will be accessible #}
    {% include 'foo' with {'foo': 'bar'} only %}

.. code-block:: jinja

    {# no variable will be accessible #}
    {% include 'foo' only %}

.. tip::

    When including a template created by an end user, you should consider
    sandboxing it. More information in the :doc:`Twig for Developers<../api>`
    chapter and in the :doc:`sandbox<../tags/sandbox>` tag documentation.

The template name can be any valid Twig expression:

.. code-block:: jinja

    {% include some_var %}
    {% include ajax ? 'ajax.html' : 'not_ajax.html' %}

And if the expression evaluates to a ``Twig_Template`` object, Twig will use it
directly::

    // {% include template %}

    $template = $twig->loadTemplate('some_template.twig');

    $twig->loadTemplate('template.twig')->display(array('template' => $template));

.. versionadded:: 1.2
    The ``ignore missing`` feature has been added in Twig 1.2.

You can mark an include with ``ignore missing`` in which case Twig will ignore
the statement if the template to be ignored does not exist. It has to be
placed just after the template name. Here some valid examples:

.. code-block:: jinja

    {% include "sidebar.html" ignore missing %}
    {% include "sidebar.html" ignore missing with {'foo': 'bar} %}
    {% include "sidebar.html" ignore missing only %}

.. versionadded:: 1.2
    The possibility to pass an array of templates has been added in Twig 1.2.

You can also provide a list of templates that are checked for existence before
inclusion. The first template that exists will be included:

.. code-block:: jinja

    {% include ['page_detailed.html', 'page.html'] %}

If ``ignore missing`` is given, it will fall back to rendering nothing if none
of the templates exist, otherwise it will throw an exception.
