``parent``
==========

.. versionadded:: 1.28
    Ability to pass variables to parent function was added in Twig 1.28.

When a template uses inheritance, it's possible to render the contents of the
parent block when overriding a block by using the ``parent`` function:

.. code-block:: jinja

    {% extends "base.html" %}

    {% block sidebar %}
        <h3>Table Of Contents</h3>
        ...
        {{ parent() }}
    {% endblock %}

The ``parent()`` call will return the content of the ``sidebar`` block as
defined in the ``base.html`` template.

The context is passed by default to the parent block but you can also pass
additional variables:

.. code-block:: jinja

    {# parent block will have access to the variables from the current context and the additional ones provided #}
    {{ parent({foo: 'bar'}) }}

You can disable access to the context by setting ``with_context`` to
``false``:

.. code-block:: jinja

    {# only the foo variable will be accessible #}
    {{ parent({foo: 'bar'}, with_context = false) }}

Arguments
---------

* ``variables``:      The variables to pass to the block
* ``with_context``:   Whether to pass the current context variables or not


.. seealso:: :doc:`extends<../tags/extends>`, :doc:`block<../functions/block>`, :doc:`block<../tags/block>`
