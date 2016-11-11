``block``
=========

.. versionadded:: 1.28
    Ability to pass variables to block function was added in Twig 1.28.

When a template uses inheritance and if you want to print a block multiple
times, use the ``block`` function:

.. code-block:: jinja

    <title>{% block title %}{% endblock %}</title>

    <h1>{{ block('title') }}</h1>

    {% block body %}{% endblock %}

The context is passed by default to the block but you can also pass
additional variables:

.. code-block:: jinja

    {# block title will have access to the variables from the current context and the additional ones provided #}
    {{ block('title', {foo: 'bar'}) }}

You can disable access to the context by setting ``with_context`` to
``false``:

.. code-block:: jinja

    {# only the foo variable will be accessible #}
    {{ block('title', {foo: 'bar'}, with_context = false) }}

Arguments
---------

* ``name``:           The block to render
* ``variables``:      The variables to pass to the block
* ``with_context``:   Whether to pass the current context variables or not

.. seealso:: :doc:`extends<../tags/extends>`, :doc:`parent<../functions/parent>`, :doc:`include<../functions/include>`
